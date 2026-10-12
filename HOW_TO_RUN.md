# CatalogIQ — How to Run Locally

> **Stack:** Symfony 7.1 (API) · Vue 3 + Vuex (Frontend) · MySQL (Source of Truth) · Elasticsearch (Search Engine)
> **PHP:** ≥ 8.2 | **Node.js:** ≥ 18 | **Composer:** ≥ 2 | **MySQL:** 9.1+ | **Elasticsearch:** 8.x / 9.x

---

## Prerequisites

Make sure the following are installed and running before starting:

| Tool | Version | Notes |
|------|---------|-------|
| PHP | ≥ 8.2 | Must have `ext-ctype`, `ext-iconv` enabled |
| Composer | ≥ 2 | [getcomposer.org](https://getcomposer.org) |
| Node.js | ≥ 18 | [nodejs.org](https://nodejs.org) |
| MySQL | 9.1+ | Running via WAMP on port 3306 |
| Elasticsearch | 8.x / 9.x | Running on port 9200 |
| WAMP64 | Any | Used as the local PHP/MySQL server |

---

## 1. Environment Setup

The `.env` file is already pre-configured for local development. Verify these values match your setup:

```env
APP_ENV=dev
DATABASE_URL="mysql://root:@127.0.0.1:3306/catalogiq?serverVersion=9.1.0&charset=utf8mb4"
ELASTICSEARCH_URL=http://localhost:9200
```

> If your MySQL has a password, update `DATABASE_URL` accordingly: `mysql://root:YOUR_PASSWORD@127.0.0.1:3306/catalogiq`

---

## 2. Backend — Symfony Setup

Open a terminal in `c:\wamp64\www\CatalogIQ\` and run:

### Step 2.1 — Install PHP Dependencies

```bash
composer install
```

### Step 2.2 — Create the Database

```bash
php bin/console doctrine:database:create
```

### Step 2.3 — Run Migrations

```bash
php bin/console doctrine:migrations:migrate
```

> Type `yes` when prompted to confirm.

### Step 2.4 — Seed the Database (~500 products, 10 categories, 20 brands)

```bash
php bin/console app:seed-products
```

### Step 2.5 — Start the Symfony Dev Server

```bash
php -S localhost:8000 -t public/
```

> The Symfony API will be available at **http://localhost:8000**

#### Verify the API is working:
```
GET http://localhost:8000/api/products
GET http://localhost:8000/api/categories
GET http://localhost:8000/api/brands
GET http://localhost:8000/api/search?q=laptop
```

---

## 3. Elasticsearch Setup

> **Elasticsearch must be running on `http://localhost:9200` before this step.**

### Step 3.1 — Index all products into Elasticsearch

```bash
php bin/console app:elastic-index
```

This command:
- Fetches all products from MySQL in batches of 100
- Creates the `catalogiq_products` index with synonym + ngram analyzers
- Bulk-indexes everything with a progress bar

#### Verify Elasticsearch is working:
```
GET http://localhost:9200/catalogiq_products/_count
```

---

## 4. Frontend — Vue 3 Setup

Open a **new terminal** in `c:\wamp64\www\CatalogIQ\frontend\` and run:

### Step 4.1 — Install Node Dependencies

```bash
npm install
```

### Step 4.2 — Start the Vite Dev Server

```bash
npm run dev
```

> The frontend will be available at **http://localhost:5173**

The Vite dev server automatically proxies all `/api/*` requests to `http://localhost:8000`, so no CORS issues.

---

## 5. Full Startup Checklist

Run these in order every time you start the project:

```
[ ] 1. Start WAMP (MySQL must be running on port 3306)
[ ] 2. Start Elasticsearch (port 9200)
[ ] 3. Terminal 1 (backend):  cd c:\wamp64\www\CatalogIQ
                               php -S localhost:8000 -t public/
[ ] 4. Terminal 2 (frontend): cd c:\wamp64\www\CatalogIQ\frontend
                               npm run dev
[ ] 5. Open browser:          http://localhost:5173
```

---

## 6. Available API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/search` | Product search with MySQL, Elasticsearch, or both; Elasticsearch requests fall back to MySQL when unavailable |
| `GET` | `/api/autocomplete` | Typeahead suggestions (min 2 chars), using the selected engine with MySQL fallback |
| `GET` | `/api/products` | Paginated product listing |
| `GET` | `/api/products/{slug}` | Single product detail |
| `GET` | `/api/categories` | All categories with product count |
| `GET` | `/api/brands` | All brands with product count |

### Search Query Params (`/api/search`)
```
?q=              Full-text query string
&page=           Page number (default: 1)
&limit=          Results per page (default: 20)
&sort=           relevance | price_asc | price_desc | rating | newest
&category_id[]=  Filter by category ID(s)
&brand_id[]=     Filter by brand ID(s)
&price_min=      Minimum price
&price_max=      Maximum price
&rating_min=     Minimum star rating
&engine=         mysql | es | both (default: both)
```

`engine=mysql` runs MySQL only; `engine=es` prefers Elasticsearch and falls back to MySQL if Elasticsearch is unavailable or errors; `engine=both` runs both and uses Elasticsearch results when available. The response's `meta.engine` identifies which engine supplied the results.

`/api/autocomplete` accepts the same `engine` values. MySQL selection uses MySQL suggestions; Elasticsearch and Both prefer Elasticsearch suggestions and fall back to MySQL if needed.

---

## 7. Useful Console Commands

```bash
# Clear Symfony cache
php bin/console cache:clear

# Re-run database migrations
php bin/console doctrine:migrations:migrate

# Re-seed the database (drops and re-creates all data)
php bin/console app:seed-products

# Re-index Elasticsearch (drops and recreates the index)
php bin/console app:elastic-index

# Check Doctrine entity mapping
php bin/console doctrine:mapping:info

# List all registered routes
php bin/console debug:router
```

---

## 8. Project Structure (Quick Reference)

```
CatalogIQ/
├── .env                          <- Environment config (DB, ES URLs)
├── PLAN.md                       <- Full project plan & progress tracker
├── HOW_TO_RUN.md                 <- This file
├── composer.json                 <- PHP dependencies
├── public/index.php              <- Symfony entry point
├── src/
│   ├── Command/
│   │   ├── SeedProductsCommand.php     <- php bin/console app:seed-products
│   │   └── ElasticIndexCommand.php     <- php bin/console app:elastic-index
│   ├── Controller/
│   │   ├── SearchController.php        <- GET /api/search, /api/autocomplete
│   │   ├── ProductController.php       <- GET /api/products, /api/products/{slug}
│   │   ├── CategoryController.php      <- GET /api/categories
│   │   └── BrandController.php         <- GET /api/brands
│   ├── Entity/                         <- Product, Category, Brand
│   ├── Repository/                     <- MySQL queries + FULLTEXT search
│   ├── Factory/
│   │   └── ElasticsearchClientFactory.php
│   └── Service/
│       └── ElasticsearchService.php    <- ES index, search, autocomplete
├── migrations/                   <- Doctrine DB migrations
└── frontend/                     <- Vue 3 + Vuex + Vite
    ├── vite.config.js            <- Proxy /api -> localhost:8000, port 5173
    ├── package.json
    └── src/
        ├── main.js
        ├── App.vue
        ├── router/index.js       <- / and /product/:slug routes
        ├── store/modules/        <- search.js, filters.js, products.js
        ├── services/api.js       <- Axios wrapper for all API calls
        ├── views/                <- SearchView.vue, ProductView.vue
        └── components/           <- layout, search, filters, product, ui, comparison
```

---

## 9. Troubleshooting

### ❌ "Connection refused" on port 8000
- Make sure you ran `php -S localhost:8000 -t public/` from the project root
- Check PHP is in your PATH: `php --version`

### ❌ Elasticsearch connection error
- Make sure Elasticsearch is running: `curl http://localhost:9200`
- The app **gracefully falls back to MySQL-only** if ES is down
- Re-index after starting ES: `php bin/console app:elastic-index`

### ❌ "Unknown database 'catalogiq'"
- Create the DB: `php bin/console doctrine:database:create`
- Then run migrations: `php bin/console doctrine:migrations:migrate`

### ❌ CORS errors in browser
- Make sure the Symfony server is running on port `8000`
- Vite proxy is configured for `/api/*` -> `localhost:8000` in `vite.config.js`

### ❌ Frontend not loading data
- Confirm the backend is running: `curl http://localhost:8000/api/categories`
- Check the browser console for the exact error URL

---

*Last updated: 2026-10-11*
