# CatalogIQ

CatalogIQ is a product catalog and search demo. It uses a Symfony API, a Vue 3 frontend, MySQL for product data and MySQL FULLTEXT search, and Elasticsearch for search and autocomplete. The interface lets you compare MySQL and Elasticsearch results.

## Requirements

Install and start the following on your computer:

- PHP 8.2 or newer, with the `pdo_mysql`, `ctype`, and `iconv` extensions enabled
- Composer 2 or newer
- Node.js 18 or newer and npm
- MySQL 9.1 (or update the configured MySQL server version to match yours)
- Elasticsearch reachable at `http://localhost:9200` (optional for MySQL-only search; Elasticsearch features need it)

On Windows, WAMP can provide PHP and MySQL. Make sure `php`, `composer`, `node`, and `npm` work in PowerShell. You can check with `php --version`, `composer --version`, and `node --version`.

## 1. Get the project and install dependencies

Clone the repository, then open PowerShell in the project folder:

```powershell
git clone <your-repository-url> CatalogIQ
cd CatalogIQ
composer install
```

Install the frontend dependencies in a second PowerShell terminal (or after the backend setup):

```powershell
cd frontend
npm ci
cd ..
```

## 2. Configure MySQL and Elasticsearch

Start MySQL and create an empty database named `catalogiq`. For example, in the MySQL client:

```sql
CREATE DATABASE catalogiq CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

The project reads `DATABASE_URL` and `ELASTICSEARCH_URL` from Symfony's environment. The checked-in `.env` has local development defaults. To use different credentials or hosts, create a `.env.local` file in the project root (it is ignored by Git) and set values such as:

```dotenv
DATABASE_URL="mysql://YOUR_MYSQL_USER:YOUR_PASSWORD@127.0.0.1:3306/catalogiq?serverVersion=9.1.0&charset=utf8mb4"
ELASTICSEARCH_URL=http://localhost:9200
```

Change `serverVersion` to match your MySQL server. If the password contains URL-reserved characters, URL-encode them. Keep real credentials in `.env.local`; never commit them.

Start Elasticsearch if you want Elasticsearch search and autocomplete. The Symfony API can fall back to MySQL when Elasticsearch is unavailable, but the comparison and Elasticsearch features require a running Elasticsearch service.

> `compose.yaml` currently defines PostgreSQL, while this application is configured for MySQL and uses MySQL FULLTEXT queries. Do not use that Compose database as-is for this project.

## 3. Create tables and sample data

From the project root, run:

```powershell
php bin/console doctrine:migrations:migrate
php bin/console app:seed-products
```

The seed command populates the catalog with sample products, categories, and brands. Run it once on a fresh database. It recreates the sample data if run again.

If Elasticsearch is running, create its index and load the products:

```powershell
php bin/console app:elastic-index
```

Run the indexing command again after changing the sample product data. To rebuild the index from scratch, use `php bin/console app:elastic-index --reset`.

## 4. Start the application

Use two terminals, both opened in the project folder.

**Terminal 1 — Symfony API:**

```powershell
php -S 127.0.0.1:8000 -t public
```

**Terminal 2 — Vue frontend:**

```powershell
cd frontend
npm run dev
```

Open **http://localhost:5173** in your browser. Keep both terminals running while using the app. The frontend sends API requests to `http://localhost:8000/api`.

## API endpoints

- `GET http://localhost:8000/api/products` — browse products
- `GET http://localhost:8000/api/products/{slug}` — product details
- `GET http://localhost:8000/api/categories` — categories
- `GET http://localhost:8000/api/brands` — brands
- `GET http://localhost:8000/api/search?q=laptop` — search products
- `GET http://localhost:8000/api/autocomplete?q=la` — autocomplete (use at least two characters)
- `GET http://localhost:8000/api/elastic/status` — Elasticsearch status

Search accepts filters and options including `page`, `limit`, `sort`, `category_id[]`, `brand_id[]`, `price_min`, `price_max`, `rating_min`, and `engine`. Set `engine=mysql`, `engine=es`, or `engine=both` to choose the search engine.

## Troubleshooting

- **`could not find driver` or a PDO error:** Enable PHP's `pdo_mysql` extension and restart the PHP process.
- **Database connection or access denied:** Check that MySQL is running, the `catalogiq` database exists, and `DATABASE_URL` in `.env.local` has the right username, password, host, and server version.
- **Missing tables:** Run `php bin/console doctrine:migrations:migrate` from the project root.
- **No products appear:** Run `php bin/console app:seed-products`.
- **Elasticsearch is unavailable:** Confirm it responds at the configured `ELASTICSEARCH_URL`. MySQL search can still work; start Elasticsearch and run `php bin/console app:elastic-index` for ES-backed search.
- **Frontend cannot reach the API:** Keep the Symfony server running at `127.0.0.1:8000`; the frontend currently uses that fixed API address.
- **Port already in use:** Stop the other process using port 8000 or 5173 before starting the corresponding server.

## Project layout

```text
src/             Symfony controllers, entities, repositories, services, and commands
config/          Symfony and Doctrine configuration
migrations/      Database schema migrations
public/          Symfony web root
frontend/        Vue 3 + Vite application
```
