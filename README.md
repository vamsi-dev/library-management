# Library Management System

## Project Description

This project is a Library Management System API built using Symfony and follows Domain-Driven Design principles.

## Installation Instructions

1. Clone the repository.
2. Copy `.env.dist` and rename it to `.env`
   - Add your own 32 digit alphanumerical string to `APP_SECRET` (Ex: **76a1f96fd36e73aae18779de9903033f**)
   - Uncomment and update `DATABASE_URL="mysql://username:password@127.0.0.1:3306/database?serverVersion=mariadb-10.4.8&charset=utf8mb4"` value with proper database credentials
   - Change **username** with your database username, **password** with your databse password and **database** with your database name in above line
3. Install dependencies:
   `composer install`
4. Set up the database:
   - Set up Dev environment
     - `php bin/console doctrine:database:create` (This command will create database)
     - `php bin/console doctrine:migrations:migrate` (This command will create tables and its schema)
     - `php bin/console doctrine:fixtures:load` (This command will add dummy data to begain with in dev environment)
5. Run the Symfony server
   - `symfony server:start`
6. To access API documentation
   - `http://127.0.0.1:8000/api/doc`
7. To run test cases
   - Set up Test environment
     - `php bin/console --env=test doctrine:database:create` (This command will create database for test environment)
     - `php bin/console --env=test doctrine:migrations:migrate` (This command will create tables and its schema for test environment)
     - `php bin/console --env=test doctrine:fixtures:load` (This command will add dummy data to run unit test in test environment)
   - Command to execute test cases
     - `php vendor/bin/phpunit`

# API Documentation

## Users

- Create User: POST /api/user
- Get User by ID: GET /api/user/{id}
- Update User: PUT /api/user/{id}
- Delete User: DELETE /api/user/{id}
- Get All Users: GET /api/user
- Borrow Book: POST /api/user/{userId}/borrow/{bookId}
- Return Book: POST /api/user/{userId}/return/{bookId}

# Books

- Create Book: POST /api/book
- Get Book by ID: GET /api/book/{id}
- Update Book: PUT /api/book/{id}
- Delete Book: DELETE /api/book/{id}
- Get All Books: GET /api/book

# Testing Instructions

- Use the provided Postman collection to test the API endpoints.

# Screenshots

![screenshot](screenshots/SWG_DOC.png)
