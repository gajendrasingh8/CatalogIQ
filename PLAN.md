# CatalogIQ — Full Project Plan

> **Stack:** Symfony 7.1 (API) · Vue 3 + Vuex (Frontend) · MySQL (Source of Truth) · Elasticsearch (Search Engine) · Redis (Cache, optional)
> **Created:** 2026-10-07

---

## ✅ Already Done

- [x] Symfony project scaffolded
- [x] `Product`, `Category`, `Brand` entities with ORM mappings, indexes, serializer groups
- [x] `ProductRepository` — FULLTEXT search, LIKE fallback, facets, pagination, sorting, filters
- [x] `BrandRepository` — `findAllWithProductCount()`
- [x] `CategoryRepository` — `findAllWithProductCount()`
- [x] `SeedProductsCommand` — seeds ~500 products, 10 categories, 20 brands
- [x] `Product::toElasticDocument()` — ready for Elasticsearch indexing
- [x] CORS configured via Nelmio (allows all origins on `/api/*`)
- [x] MySQL DB configured (catalogiq database)

---

## 🚧 Phase 1 — Backend: Database & API Controllers

### Step 1.1 — Database Migration & Seed
- [ ] Run `php bin/console doctrine:migrations:diff` to generate migration
- [ ] Run `php bin/console doctrine:migrations:migrate` to create tables
- [ ] Run `php bin/console app:seed-products` to populate data

### Step 1.2 — API Controllers (`src/Controller/`)

#### `SearchController.php`
- [ ] `GET /api/search` — Main search endpoint
  - Params: `q`, `page`, `limit`, `sort`, `engine=mysql|es|both`, filters
  - Returns selected results, pagination meta, facets, and timings; `meta.engine` identifies the result source
  - `both` runs both paths and prefers ES results; ES requests fall back to MySQL if ES is unavailable or errors

- [ ] `GET /api/autocomplete` — Typeahead suggestions
  - Params: `q` (min 2 chars), `engine=mysql|es|both`
  - Returns: top 8 matching product names + categories

#### `ProductController.php`
- [ ] `GET /api/products` — Paginated product listing (no search, browse mode)
- [ ] `GET /api/products/{slug}` — Single product detail

#### `CategoryController.php`
- [ ] `GET /api/categories` — All categories with product count

#### `BrandController.php`
- [ ] `GET /api/brands` — All brands with product count

### Step 1.3 — Response Format (consistent JSON)
```json
{
  "data": [...],
  "meta": {
    "total": 500,
    "page": 1,
    "limit": 20,
    "pages": 25
  },
  "timing": {
    "mysql_ms": 45.2,
    "es_ms": 3
  }
}
```

---

## 🚧 Phase 2 — Elasticsearch Integration

### Step 2.1 — Install Elasticsearch PHP Client
- [ ] `composer require elasticsearch/elasticsearch`
- [ ] Add `ELASTICSEARCH_URL=http://localhost:9200` to `.env`
- [ ] Register `ElasticsearchClient` as a Symfony service in `services.yaml`

### Step 2.2 — Elasticsearch Service (`src/Service/ElasticsearchService.php`)
- [ ] `createIndex()` — Create `catalogiq_products` index with mappings
- [ ] `indexProduct(Product $product)` — Index single product
- [ ] `bulkIndex(array $products)` — Bulk index
- [ ] `search(string $query, array $filters, int $page, string $sort)` — results + timing
- [ ] `autocomplete(string $query)` — suggestions
- [ ] `deleteIndex()` — Drop and recreate index

### Step 2.3 — Index Settings (Typo Tolerance + Synonyms)
Index analyzer config:
- synonym_filter: mobile/phone/smartphone, sneakers/shoes, laptop/notebook, earphones/earbuds/headphones
- autocomplete_filter: edge_ngram (min_gram: 2, max_gram: 20)
- search_analyzer: standard tokenizer + lowercase + synonym_filter
- autocomplete_analyzer: standard tokenizer + lowercase + autocomplete_filter

### Step 2.4 — Elasticsearch Sync Command
- [ ] `ElasticIndexCommand.php` (`app:elastic-index`)
  - Fetches all products from MySQL in batches of 100
  - Bulk-indexes them into Elasticsearch
  - Shows progress bar

### Step 2.5 — Fuzzy Search (Typo Tolerance)
- Use `multi_match` with `fuzziness: AUTO` on name^3, description, tags^2
- Analyzer: search_analyzer (handles synonyms)

---

## 🚧 Phase 3 — Vue 3 Frontend

### Step 3.1 — Project Setup
- [ ] Scaffold: `npm create vite@latest frontend -- --template vue`
- [ ] Install: `npm install vuex@4 vue-router@4 axios`
- [ ] Lives at: `c:\wamp64\www\CatalogIQ\frontend\`

### Step 3.2 — Directory Structure
```
frontend/
├── public/
└── src/
    ├── main.js
    ├── App.vue
    ├── router/
    │   └── index.js
    ├── store/
    │   ├── index.js
    │   └── modules/
    │       ├── search.js
    │       ├── filters.js
    │       └── products.js
    ├── services/
    │   └── api.js
    ├── views/
    │   ├── SearchView.vue
    │   └── ProductView.vue
    └── components/
        ├── layout/
        │   ├── AppHeader.vue
        │   └── AppFooter.vue
        ├── search/
        │   ├── SearchBar.vue
        │   ├── AutocompleteDropdown.vue
        │   ├── SearchResults.vue
        │   ├── ResultsMeta.vue
        │   └── Pagination.vue
        ├── filters/
        │   ├── FilterSidebar.vue
        │   ├── CategoryFilter.vue
        │   ├── BrandFilter.vue
        │   ├── PriceRangeFilter.vue
        │   ├── RatingFilter.vue
        │   └── ActiveFilters.vue
        ├── product/
        │   ├── ProductCard.vue
        │   └── ProductDetail.vue
        ├── comparison/
        │   └── TimingComparison.vue
        └── ui/
            ├── StarRating.vue
            ├── PriceBadge.vue
            ├── StockBadge.vue
            └── LoadingSpinner.vue
```

### Step 3.3 — Vuex Store Structure

#### `store/modules/search.js`
```
State:    query, results, total, page, limit, sort, engine, resultEngine, loading, timing, autocomplete
Mutations: SET_QUERY, SET_RESULTS, SET_TOTAL, SET_PAGE, SET_SORT, SET_ENGINE, SET_RESULT_ENGINE, SET_LOADING, SET_TIMING, SET_AUTOCOMPLETE
Actions:  fetchResults({ query, filters, page, sort, engine }), fetchAutocomplete({ query })
Getters:  totalPages, hasResults, isLoading
```

#### `store/modules/filters.js`
```
State:    facets: { categories, brands, price_ranges, ratings }, active: { category_id[], brand_id[], price_min, price_max, rating_min }
Mutations: SET_FACETS, TOGGLE_CATEGORY, TOGGLE_BRAND, SET_PRICE_RANGE, SET_RATING_MIN, CLEAR_ALL_FILTERS
Actions:  applyFilter(type, value) -> commits -> triggers re-fetch
Getters:  activeFilterCount, hasActiveFilters
```

#### `store/modules/products.js`
```
State:    current: null, loading: false
Mutations: SET_PRODUCT, SET_LOADING
Actions:  fetchProduct(slug)
```

### Step 3.4 — Key Components

#### `SearchBar.vue`
- Debounced input (300ms) -> dispatches fetchAutocomplete
- On Enter/click -> dispatches fetchResults
- Keyboard nav on autocomplete (up/down/Enter/Esc)

#### `TimingComparison.vue` ⭐ (Project's biggest highlight)
- Side-by-side MySQL ms vs Elasticsearch ms
- Timing bars for engines requested by the selected search mode
- MySQL reports API-side elapsed time; Elasticsearch reports server-side `took`
- MySQL = amber color, ES = green color
- Updates on every search

#### `FilterSidebar.vue`
- Facets update reactively after each search
- Each checkbox shows count e.g. "Apple (42)"
- Active filters trigger new search + update counts

#### `Pagination.vue`
- Page numbers, prev/next buttons
- Syncs with URL query params via Vue Router

### Step 3.5 — API Service (`services/api.js`)
```
baseURL: http://localhost:8000/api

searchProducts(params)   -> GET /search
getAutocomplete(q, engine) -> GET /autocomplete
getProduct(slug)         -> GET /products/:slug
getCategories()          -> GET /categories
getBrands()              -> GET /brands
```

### Step 3.6 — Vue Router
```
/                     -> SearchView (default, all products)
/?q=iphone&page=2     -> SearchView with query + page in URL
/product/:slug        -> ProductView
```
- URL stays in sync with Vuex state (push to history on each search)

---

## 🚧 Phase 4 — Nice-to-Have

### Step 4.1 — Search Result Highlighting
- [ ] ES highlight field in query response
- [ ] ProductCard.vue renders <em> highlighted snippets

### Step 4.2 — "No Results" Suggestions
- [ ] API returns spelling suggestion when 0 results (ES suggest API)
- [ ] Frontend shows "Did you mean: iPhone?" link

### Step 4.3 — Redis Cache
- [ ] `composer require symfony/cache`
- [ ] Cache popular searches (TTL: 5 min) in Redis
- [ ] Add `REDIS_URL=redis://localhost:6379` to `.env`

### Step 4.4 — Admin Page
- [ ] New Vue view: `AdminView.vue` (route: /admin)
- [ ] Symfony: `GET /api/admin/stats` — top queries, zero-result queries
- [ ] Table showing search analytics

### Step 4.5 — Cart Flow (Basic)
- [ ] Vuex `cart.js` module: items[], addToCart, removeFromCart
- [ ] `CartIcon.vue` in header with item count badge
- [ ] `CartDrawer.vue` slide-in panel

---

## 📋 Execution Checklist (In Order)

### Phase 1 — Backend
- [x] 1.1  `doctrine:migrations:diff` + `migrate`
- [x] 1.2  `app:seed-products`
- [x] 1.3  `SearchController` — `GET /api/search`
- [x] 1.4  `ProductController` — `GET /api/products`, `GET /api/products/{slug}`
- [x] 1.5  `CategoryController` — `GET /api/categories`
- [x] 1.6  `BrandController` — `GET /api/brands`
- [x] 1.7  `SearchController` — `GET /api/autocomplete`

### Phase 2 — Elasticsearch
- [x] 2.1  `composer require elasticsearch/elasticsearch`
- [x] 2.2  `ElasticsearchService` (createIndex, indexProduct, search, autocomplete)
- [x] 2.3  Index settings: synonyms + ngram analyzer
- [x] 2.4  `ElasticIndexCommand` (`app:elastic-index`)
- [x] 2.5  Wire ES into SearchController for dual MySQL+ES timing response

### Phase 3 — Vue Frontend
- [x] 3.1  Vite + Vue 3 scaffold in /frontend
- [x] 3.2  Install vuex@4, vue-router@4, axios
- [x] 3.3  Router setup (/, /product/:slug)
- [x] 3.4  Vuex store: search.js + filters.js + products.js
- [x] 3.5  services/api.js (Axios wrapper)
- [x] 3.6  AppHeader + SearchBar + AutocompleteDropdown
- [x] 3.7  SearchView layout (FilterSidebar + results grid)
- [x] 3.8  FilterSidebar + CategoryFilter + BrandFilter + PriceRangeFilter + RatingFilter + ActiveFilters
- [x] 3.9  SearchResults + ProductCard
- [x] 3.10 Pagination + ResultsMeta
- [x] 3.11 TimingComparison panel (MySQL vs ES ⭐)
- [x] 3.12 ProductView (detail page)

### Phase 4 — Nice-to-Have
- [ ] 4.1  Highlighting
- [ ] 4.2  No-results suggestions
- [ ] 4.3  Redis cache
- [ ] 4.4  Admin page
- [ ] 4.5  Cart flow

---

## 🗂️ Final Project Structure

```
CatalogIQ/
├── PLAN.md                              <- This file
├── .env
├── composer.json
├── config/
├── migrations/
├── src/
│   ├── Command/
│   │   ├── SeedProductsCommand.php      ✅ done
│   │   └── ElasticIndexCommand.php      ✅ done Phase 2
│   ├── Controller/
│   │   ├── SearchController.php         ✅ done (Phase 1 + 2)
│   │   ├── ProductController.php        ✅ done
│   │   ├── CategoryController.php       ✅ done
│   │   └── BrandController.php          ✅ done
│   ├── Entity/
│   │   ├── Product.php                  ✅ done
│   │   ├── Category.php                 ✅ done
│   │   └── Brand.php                    ✅ done
│   ├── Repository/
│   │   ├── ProductRepository.php        ✅ done
│   │   ├── CategoryRepository.php       ✅ done
│   │   └── BrandRepository.php         ✅ done
│   ├── Factory/
│   │   └── ElasticsearchClientFactory.php ✅ done Phase 2
│   └── Service/
│       └── ElasticsearchService.php     ✅ done Phase 2
└── frontend/                            <- TODO Phase 3 (Vue 3 + Vuex)
    ├── package.json
    ├── vite.config.js
    └── src/
        ├── main.js
        ├── App.vue
        ├── router/index.js
        ├── store/
        │   ├── index.js
        │   └── modules/
        │       ├── search.js
        │       ├── filters.js
        │       └── products.js
        ├── services/api.js
        ├── views/
        │   ├── SearchView.vue
        │   └── ProductView.vue
        └── components/
            ├── layout/   (AppHeader, AppFooter)
            ├── search/   (SearchBar, AutocompleteDropdown, SearchResults, ResultsMeta, Pagination)
            ├── filters/  (FilterSidebar, CategoryFilter, BrandFilter, PriceRangeFilter, RatingFilter, ActiveFilters)
            ├── product/  (ProductCard, ProductDetail)
            ├── comparison/ (TimingComparison)
            └── ui/       (StarRating, PriceBadge, StockBadge, LoadingSpinner)
```

