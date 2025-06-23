
DROP TABLE IF EXISTS bookings, rooms, room_types, users;

/* ---------- 1. Users (auth & roles + email verification) ---------- */
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    role          ENUM('customer','employee','admin')  NOT NULL DEFAULT 'customer',
    email         VARCHAR(255)                         NOT NULL UNIQUE,
    email_verified BOOLEAN                             NOT NULL DEFAULT FALSE,
    email_verification_token VARCHAR(255)              NULL,
    email_verification_expires TIMESTAMP               NULL,
    password_hash VARCHAR(255)                         NOT NULL,
    first_name    VARCHAR(60)                          NOT NULL,
    last_name     VARCHAR(60)                          NOT NULL,
    phone         VARCHAR(20)                          NULL,
    is_active     BOOLEAN                              NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP                            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (email <> '')
);

/* ---------- 2. Room types (price / capacity) ---------- */
CREATE TABLE room_types (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(80)     NOT NULL UNIQUE,
    capacity         TINYINT UNSIGNED NOT NULL,
    price_night      DECIMAL(8,2)    NOT NULL,
    location         ENUM('cabin','hotel') NOT NULL,
    CHECK (capacity BETWEEN 1 AND 4),
    CHECK (price_night > 0)
);

/* ---------- 3. Rooms (physical units) ---------- */
CREATE TABLE rooms (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    number        VARCHAR(10)  NOT NULL UNIQUE,
    room_type_id  INT          NOT NULL,
    floor         TINYINT      NOT NULL,
    status        ENUM('available','occupied','cleaning','maintenance','out_of_order') NOT NULL DEFAULT 'available',
    FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

/* ---------- 4. Bookings (Simple first-come-first-serve) ---------- */
CREATE TABLE bookings (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    ref_code     CHAR(10)       NULL ,
    customer_id  INT            NOT NULL,
    room_id      INT            NOT NULL,
    status       ENUM('confirmed','checked_in','checked_out','cancelled') NOT NULL DEFAULT 'confirmed',
    check_in     DATE           NOT NULL,
    check_out    DATE           NOT NULL,
    guests       TINYINT UNSIGNED NOT NULL,
    nights       INT            NOT NULL,
    total_amount DECIMAL(8,2)   NOT NULL,            
    special_requests TEXT       NULL,
    created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (room_id)      REFERENCES rooms(id),
    CHECK (guests BETWEEN 1 AND 4),
    CHECK (check_in  < check_out),
    CHECK (total_amount > 0),
    CHECK (nights > 0)
);

/* ============================================================
    INDEXES
   ============================================================ */

/* ---------- User Indexes ---------- */
-- Index for email verification token lookup
CREATE INDEX idx_users_verification_token ON users(email_verification_token);

-- Index for email verification status
CREATE INDEX idx_users_email_verified ON users(email_verified);

-- Index for user role filtering
CREATE INDEX idx_users_role ON users(role);

-- Index for active users
CREATE INDEX idx_users_is_active ON users(is_active);

/* ---------- Room Indexes ---------- */
-- Index for room status filtering (most common filter)
CREATE INDEX idx_rooms_status ON rooms(status);

-- Index for room type filtering
CREATE INDEX idx_rooms_room_type_id ON rooms(room_type_id);

-- Index for floor filtering
CREATE INDEX idx_rooms_floor ON rooms(floor);

-- Composite index for common filter combinations
CREATE INDEX idx_rooms_type_status ON rooms(room_type_id, status);
CREATE INDEX idx_rooms_status_floor ON rooms(status, floor);

/* ---------- Room Type Indexes ---------- */
-- Index for room types location filtering
CREATE INDEX idx_room_types_location ON room_types(location);

-- Index for room types capacity filtering
CREATE INDEX idx_room_types_capacity ON room_types(capacity);

-- Composite index for room types filtering
CREATE INDEX idx_room_types_location_capacity ON room_types(location, capacity);

/* ---------- Booking Indexes ---------- */
-- Booking system indexes for better performance
CREATE INDEX idx_bookings_customer_id ON bookings(customer_id);
CREATE INDEX idx_bookings_room_id ON bookings(room_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_bookings_dates ON bookings(check_in, check_out);
CREATE INDEX idx_bookings_ref_code ON bookings(ref_code);

-- Composite indexes for common booking queries
CREATE INDEX idx_bookings_room_dates_status ON bookings(room_id, check_in, check_out, status);
CREATE INDEX idx_bookings_customer_status ON bookings(customer_id, status);

/* ============================================================
   DATABASE TRIGGERS
   ============================================================ */

/* ---------- Booking Overlap Prevention ---------- */
DELIMITER //
CREATE TRIGGER bookings_no_overlap
BEFORE INSERT ON bookings
FOR EACH ROW
BEGIN
  IF EXISTS (
      SELECT 1 FROM bookings
      WHERE room_id = NEW.room_id
        AND status NOT IN ('cancelled')
        AND NEW.check_in  < check_out
        AND NEW.check_out > check_in
  )
  THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Room already booked for that period';
  END IF;
END//
DELIMITER ;

DELIMITER //
CREATE TRIGGER bookings_no_overlap_update
BEFORE UPDATE ON bookings
FOR EACH ROW
BEGIN
  IF NEW.status NOT IN ('cancelled') THEN
    IF EXISTS (
        SELECT 1 FROM bookings
        WHERE room_id = NEW.room_id
          AND id != NEW.id
          AND status NOT IN ('cancelled')
          AND NEW.check_in  < check_out
          AND NEW.check_out > check_in
    )
    THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Room already booked for that period';
    END IF;
  END IF;
END//
DELIMITER ;

