# Corner Shop Vending

A small, server-rendered PHP 8.2+ vending machine application. It uses PHP attributes for routes, PDO for MySQL, PHP sessions for the website, and short-lived HS256 bearer tokens for the API.

## What it does

- Lists drinks with pagination and sorting.
- Lets admins add, edit, and remove products, and review recent sales.
- Lets registered users purchase one or more units. A stock lock, quantity update, and sale record happen in one database transaction.
- Keeps sales linked to their user and product; a product with sales cannot be deleted.
- Validates product details in the browser and again on the server.
- Offers a JSON API. Anyone can read the product list; signed-in users can buy; admins can manage stock.

Prices are kept to three decimal places so `Pepsi` can be stored exactly as `$6.885`. The starter inventory is set to 10 each; change those seed quantities in `database/schema.sql` if your machine has a different count.

## Database design

`database/schema.sql` creates:

- `products`: product name, `DECIMAL(10,3)` price, and unsigned stock count.
- `users`: unique email, password hash (never the raw password), and `Admin` / `User` role.
- `transactions`: buyer, product, quantity, unit price, total, and timestamp. Foreign keys preserve sales history by preventing deletion of users or sold products.

The purchase service locks the selected product row with `SELECT ... FOR UPDATE`, checks the remaining stock, decrements inventory, and inserts the transaction before committing. A failure rolls the entire operation back.

## Run locally

Requirements: PHP 8.2+, Composer, MySQL 8+, and PDO MySQL.

1. Copy `.env.example` to `.env`. Set database credentials and replace both secret values with independent random strings (32+ characters).
2. Install dependencies:

   ```sh
   composer install
   ```

3. Create tables and starter products:

   ```sh
   mysql -u root -p < database/schema.sql
   ```

4. Create the first admin account using environment variables (keep the password out of source control):

   ```sh
   ADMIN_EMAIL=admin@example.com ADMIN_PASSWORD='a-long-unique-password' php bin/create-admin.php
   ```

5. Start the development server:

   ```sh
   php -S 127.0.0.1:8000 -t public public/index.php
   ```

Open [http://127.0.0.1:8000](http://127.0.0.1:8000). New registrations are always `User`; only the CLI setup creates the first `Admin`.

## API

All responses are JSON under `/api`. For write calls send `Content-Type: application/json`.

- `POST /api/token` with `{"email":"...","password":"..."}` returns a bearer token.
- `GET /api/products?page=1&sort=name&direction=ASC` lists products.
- `GET /api/products/{id}` shows one product.
- `POST /api/products/{id}/purchase` with `{"quantity":1}` requires `Authorization: Bearer <token>`.
- `POST /api/products`, `PUT /api/products/{id}`, and `DELETE /api/products/{id}` require an Admin bearer token.

Example:

```sh
curl -X POST http://127.0.0.1:8000/api/token \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"a-long-unique-password"}'
```

Set `JWT_TTL` to change the token lifetime in seconds. Use HTTPS in deployment and keep `.env`, `database/`, and `vendor/` outside the web document root; point the server document root at `public/`.

## Tests

```sh
composer test
```

Controller tests use mocks so they do not need a running database.

## Deploying

For a real preview URL, deploy `public/` as the document root on a PHP/MySQL host, provision a MySQL database, set the production environment variables, run the schema, and create an admin. This project folder does not include access to a hosting account or a domain, so it cannot be published from here without those credentials.
# vending-machine
