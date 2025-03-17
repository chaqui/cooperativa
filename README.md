# Cooperativa

Welcome to the Cooperativa project! This README will help you understand what this project is about and how to get started.

## Table of Contents
- [Introduction](#introduction)
- [Installation](#installation)

## Introduction
Cooperativa is a project for managing a cooperative society. It is built with Laravel and Vue.js.

## Installation
To install the project, follow these steps:

1. Clone the repository:
    ```sh
    git clone https://github.com/yourusername/cooperativa.git
    ```
2. Navigate to the project directory:
    ```sh
    cd cooperativa
    ```
3. Install the dependencies:
    ```sh
    npm install
    ```


## License
This project is licensed under the [MIT License](link-to-license).
## Laravel Setup

If you are using Laravel for this project, follow these additional steps to set up the Laravel environment:

1. Install Composer dependencies:
    ```sh
    composer install
    ```

2. Copy the example environment file and set the application key:
    ```sh
    cp .env.example .env
    php artisan key:generate
    ```

3. Run the database migrations:
    ```sh
    php artisan migrate
    ```

4. Start the local development server:
    ```sh
    php artisan serve
    ```

4.1. Para correr para pruebas en la red local:
    ```sh
    php artisan serve --host=0.0.0.0 --port=8000
    ```
5. Visit the application in your browser at `http://localhost:8000`.



For more detailed Laravel-specific instructions, refer to the [Laravel documentation](https://laravel.com/docs).
