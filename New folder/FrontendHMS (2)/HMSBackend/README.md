# PHP Web Development Boilerplate

## About

This repo contains some starter code for new PHP API projects.

What's included:

- Docker setup including:
  - PHP interpreter
  - NGINX server
  - MySQL (MariaDB) database
  - PHP MyAdmin
- A directory structure organized around the MVC pattern
- Composer
- Autoload setup

## Usage

- Start local

In a terminal, from the cloned/forked/download project folder, run:

```bash
docker compose up
```

NGINX will now serve files in the app/public folder. Visit localhost in your browser to check.
PHPMyAdmin is accessible on localhost:8080

If you want to stop the containers, press Ctrl+C.

Or run:

```bash
docker compose down
```

## Composer commands

- to run composer commands, `docker compose run php composer [arguments]`
- i.e. to install the QR code library (already done): `docker compose run php composer require chillerlan/php-qrcode`

## Important files

- The entry point for the application is `app/public/index.php`. Start there and trace the control flow through the routes, controllers and models
- `insomnia_article_collection.json` can be imported into [Insomnia](https://insomnia.rest/) for API testing

## Code Architecture

The code follows an MVC pattern. Here is an outline of the code structure:

- `public/index.php` - the main entry point of the application with initialization and routes.
- `controllers` - responsible for logic
- `models` - responsible for database CRUD
- `services` - various self contained services. These are primarily used by the controllers and can contain logic for small local services or for interacting with external services (mail send, external apis, etc.).
