# Trade Centric purchase orders (CRUD APP)

Welcome to the purchase orders CRUD application! This project is built from scratch with a focus on clean code and efficient functionality. This README will guide you through the project structure, key components, and setup instructions.

**Note: This project is a proof of concept, created with the primary purpose of providing a deep dive into the code base. It aims to illustrate how components are connected to each other in a basic and simple project. Please note that this project may not be ready for production use and is intended for POC purposes.**


## Table of Contents
1. [Introduction](#introduction)
2. [Key Components](#key-components)
    - [Models](#Models)
    - [Controllers](#PurchaseOrderController)
    - [Request Classes](#Request-Classes)
    - [Factories](#factories)
    - [Resources](#Resources)
3. [Tests](#tests)
    - [Unit Tests](#unit-tests)
4. [Setup Instructions](#setup-instructions)
## Introduction

This application serves purchase orders, allowing users to show/create/update and delete purchase orders. The project emphasizes a clean and well-organized codebase to ensure maintainability and extensibility.

**MySQL:** The application uses MySQL as the database and using LARAVEL ORM to storing and managing data.


## Key Components

### Models
- PurchaseOrder
- PurchaseOrderItem
- Category

### Controllers
The `PurchaseOrderController` is the primary controller responsible for handling the purchase orders requests. This controller ensures that the CRUD process is streamlined and encapsulates the business logic.

**File:** [ShortUrlController.php](app/Http/Controllers/Api/PurchaseOrderController.php)


### Factories
I have utilized Laravel's factory feature to streamline the process of generating dummy data for testing purposes, enhancing the efficiency of our development workflow.- [StorePurchaseOrderRequest.php](app/Http/Requests/Api/StorePurchaseOrderRequest.php)
- [PurchaseOrderFactory.php](database/factories/PurchaseOrderFactory.php)
- [PurchaseOrderItemFactory.php](database/factories/PurchaseOrderItemFactory.php)

### Request Classes
I have employed Laravel's Request class to validate both create and update requests, ensuring data integrity and consistency.
- [StorePurchaseOrderRequest.php](app/Http/Requests/Api/StorePurchaseOrderRequest.php)
- [UpdatePurchaseOrderRequest.php](app/Http/Requests/Api/UpdatePurchaseOrderRequest.php)

### Resources
I have utilized Laravel resources to define the structure of the response body that the API consumer will receive.
- [PurchaseOrderResource.php](app/Http/Resources/Api/PurchaseOrderResource.php)
- [PurchaseOrderResourceItem.php](app/Http/Resources/Api/PurchaseOrderItemResource.php)
- [CategoryResource.php](app/Http/Resources/Api/CategoryResource.php)


## Tests

The application includes tests to maintain code quality and ensure the reliability of the URL shortening functionality.

### Unit Tests

Unit tests focus on individual components of services to validate their behavior in isolation. You can run unit tests using the following command:

```bash
php artisan test
````

## Setup Instructions

To set up the application on your local environment, follow these instructions:

#### Clone the repository:

```bash
git clone https://github.com/abanoubsamaan/trade-centric.git
````
#### Navigate to the project directory:

```bash
cd trade-centric
````

#### Install dependencies using Composer:

```bash
composer install
````

#### Copy the .env.example file to .env and configure your MongoDB connection details:
```bash
cp .env.example .env
````

#### Generate an application key:
```bash
php artisan key:generate
````
#### Generate an application key:
```bash
php artisan key:generate
````
#### Start the Laravel development server:
```bash
php artisan serve
````
Visit `http://localhost:8000` in your browser to access the Laravel URL Shortener.

