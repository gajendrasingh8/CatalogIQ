# CatalogIQ interview study guide

## 1. The short version

**CatalogIQ is a product-catalog search demo.** It stores product data in a relational database and uses Elasticsearch as a search index. A Symfony API connects the search system to a Vue frontend. The search endpoint can run MySQL and Elasticsearch searches, report their timings, and prefer Elasticsearch results when Elasticsearch is available.

A concise interview summary:

> “CatalogIQ explores how a dedicated search engine can improve product discovery. MySQL holds the product records, while Elasticsearch indexes searchable fields and supports fuzzy matching, synonyms, autocomplete, facets, and sorting. A Symfony API applies the query and filters, returns results and timing metadata, and falls back to MySQL when Elasticsearch is unavailable. The Vue UI lets someone try the search and compare the engines.”

**Important distinction:** Despite the project’s name, the checked-in code does not show a trained machine-learning model. The search behavior is built from Elasticsearch query and analyzer settings, plus MySQL full-text search. I can’t verify what role “Anti-Gravity” played from the repository alone.

## 2. What problem it addresses

Product catalogs are difficult to search well because customers may:

- Use a typo, partial name, or alternate term.
- Want to narrow results by brand, category, price, or rating.
- Need useful suggestions while typing.
- Expect results to sort by relevance, price, date, or rating.

A basic relational query can support structured filters and some text search. Elasticsearch is designed for text retrieval and offers features such as fuzzy matching and custom analyzers. CatalogIQ puts both approaches behind one API so they can be tried side by side.

The included seed command creates synthetic sample products across categories and brands. Treat its results and timings as a demonstration dataset, not evidence of real-world catalog accuracy or production-scale performance.

## 3. Main components and data flow

```text
Seed command ──> MySQL product records ──> Elasticsearch indexing command
                        │                           │
                        └──── Symfony search API ───┘
                                      │
                                Vue frontend
```

### Product data

The `Product` entity includes fields such as name, description, price, rating, tags, stock status, category, and brand. Its `toElasticDocument()` method converts a product and its related category and brand into a flat document for Elasticsearch.

### MySQL

MySQL is intended as the source of truth: it stores products and provides a `FULLTEXT` search path. The repository also implements filters, pagination, sorting, facets, and a simpler `LIKE` autocomplete fallback.

### Elasticsearch

`ElasticsearchService` creates the `catalogiq_products` index, builds its mappings and analyzers, indexes products, and runs search and autocomplete requests. Search uses a `multi_match` query across name, tags, description, category, and brand; name and tags have higher weights, and the query enables `fuzziness: AUTO`.

The index configuration is intended to handle synonyms and autocomplete through custom analyzers, including an edge-ngram field. Search responses can also include highlight snippets and aggregations used as facets.

### Symfony API

`GET /api/search` accepts a query, page, limit, sort, category and brand IDs, price bounds, rating minimum, and an engine choice. The Vue selector offers MySQL, Elasticsearch, or Both, and stores the choice in the URL. The default is `both`: the controller runs both paths and prefers Elasticsearch results when available. If Elasticsearch is selected by itself but is unavailable or errors, the controller falls back to MySQL and reports which engine produced the results.

`GET /api/autocomplete` returns product and category suggestions using the selected engine; Elasticsearch requests fall back to MySQL `LIKE`. `GET /api/elastic/status` reports whether Elasticsearch is reachable and whether the index exists.

### Vue frontend

The Vue app sends API requests and keeps search state in Vuex. Its interface includes search, filters, result cards, pagination, product details, autocomplete, and a MySQL-versus-Elasticsearch timing panel.

## 4. Design decisions and tradeoffs

| Decision | Why it helps | Tradeoff or caveat |
|---|---|---|
| Keep catalog records in MySQL and copy search documents into Elasticsearch | Separates transactional data storage from search behavior | The two stores can drift unless updates and deletions are synchronized |
| Compare MySQL and Elasticsearch through one endpoint | Makes it easy to explore their behavior in the demo | Timings from a small local dataset do not establish production performance |
| Use fuzzy matching and synonyms | Can find results despite typos or alternate wording | More permissive matching can also introduce irrelevant results |
| Use multi-field relevance boosts | Gives product names and tags more influence than long descriptions | The boosts are fixed heuristics; relevance should be evaluated against real queries |
| Add facets and structured filters | Helps users narrow a catalog after searching | Facet counts and filter semantics need to stay consistent across engines |
| Fall back when Elasticsearch is unavailable | Preserves a usable search path during an outage | MySQL fallback may behave differently and may be slower |
| Use synthetic seeded data | Makes the demo reproducible without a live catalog feed | It cannot represent real catalog quality, traffic, or search intent |

One implementation detail to be ready to discuss: the index command supports `--reset`, which deletes and recreates the index. That is convenient for development, but a production reindex would normally need a safer cutover approach so users do not rely on an empty index while it is being rebuilt.

The indexing command also appears to load all products into memory before chunking them, even though the project plan describes fetching batches. That is acceptable for a demo dataset; for a much larger catalog, streaming or database-level batching would be more scalable.

## 5. Running it end to end

These are the repository’s command names and frontend ports. Confirm the local database and Elasticsearch settings before running them: the project notes describe MySQL, but the provided Compose file starts PostgreSQL, while the repository’s full-text SQL uses MySQL’s `MATCH ... AGAINST` syntax. This mismatch needs to be resolved for a reliable run.

1. **Check configured services.** Review `DATABASE_URL` and `ELASTICSEARCH_URL` in your local environment without sharing credentials. Confirm the database engine matches the SQL and that Elasticsearch is reachable.
2. **Install dependencies if needed.** Run `composer install` at the project root and `npm install` inside `frontend`.
3. **Create the schema.** The project notes list `php bin/console doctrine:migrations:migrate`. A migration is checked in, but successful migration depends on the configured database being compatible.
4. **Seed sample catalog records.** Run `php bin/console app:seed-products`. The command creates categories, brands, and roughly 500 sample products.
5. **Build the Elasticsearch index.** Run `php bin/console app:elastic-index`. For a clean development rebuild, the command also supports `php bin/console app:elastic-index --reset`. The command expects Elasticsearch to be reachable.
6. **Start the API.** The frontend is configured for Symfony at `http://localhost:8000`; use the project’s available Symfony/PHP serving setup.
7. **Start the frontend.** In `frontend`, run `npm run dev`; Vite is configured on port `5173` and proxies `/api` to the backend.
8. **Check and try the API.** Open `/api/elastic/status`, then try `/api/search?q=iphone` and `/api/autocomplete?q=ip`. Add filters or `engine=mysql`, `engine=es`, or `engine=both` to explore each path.
9. **Observe the UI.** Search for a product term, change a filter or sort, try autocomplete, and compare the displayed timings. Treat the timing panel as an observation tool, not as a controlled benchmark.

## 6. Hands-on sequence

Try these in order and take notes on what you actually observe:

1. **Check health:** call `/api/elastic/status`. Note whether Elasticsearch is reachable and whether the index exists.
2. **Search with Elasticsearch:** call `/api/search?q=iphone&engine=es`; inspect `data`, `meta.engine`, and `timing`.
3. **Search with MySQL:** repeat with `engine=mysql`. Compare results as well as timings; the engines need not return identical rankings or counts.
4. **Try a typo or alternate wording:** compare behavior for a misspelled product query and a synonym. Record what works rather than assuming every configured synonym behaves as intended.
5. **Apply a filter and sort:** try a price bound or brand ID, then change `sort`. Check the result list and facets.
6. **Try autocomplete:** request `/api/autocomplete?q=sa` and compare the returned suggestions with the search results.
7. **Observe fallback:** if appropriate in your local setup, make Elasticsearch unavailable and check whether the API uses MySQL. Don’t do this on a shared or production environment.

## 7. Likely interviewer questions

**What does CatalogIQ do?**  
It demonstrates product search over a sample catalog, including filtering, autocomplete, facets, and a comparison between MySQL full-text search and Elasticsearch.

**Why use Elasticsearch if the catalog is already in MySQL?**  
MySQL remains the system of record. Elasticsearch provides search-oriented features such as fuzzy matching, analyzer-based synonyms, and autocomplete. The tradeoff is that the index must be kept synchronized.

**Is this a machine-learning ranking model?**  
“From the code I reviewed, no trained model is visible. Ranking comes from Elasticsearch query scoring, field boosts, analyzers, and selected sort rules. I’d verify whether ‘Anti-Gravity’ refers to a separate model or development tool outside this repository.”

**How does a product get into Elasticsearch?**  
The index command reads products from the database, converts each entity with `toElasticDocument()`, and bulk-indexes documents under the product ID. The service refreshes the index after a bulk operation.

**What happens when Elasticsearch is down?**  
The search endpoint uses MySQL results when Elasticsearch is unavailable, including when a customer selected Elasticsearch only; the response and UI identify that fallback. Autocomplete similarly falls back to a MySQL `LIKE` query.

**How does fuzzy search work here?**  
The Elasticsearch query uses `multi_match` with `fuzziness: AUTO` across several product fields. The index also defines custom analyzers for synonyms and autocomplete. I’d verify behavior against the running index for any specific query.

**How would you keep the index current after a product changes?**  
“The code I reviewed has an initial bulk indexing path and a single-product indexing method, but I did not find a complete automatic synchronization workflow. I’d verify whether changes are handled elsewhere. For production I’d design reliable update and delete events, retries, and a reconciliation process.”

**Can we trust the timing comparison?**  
"The Elasticsearch figure is its server-reported `took` time; the MySQL figure is API-side elapsed time, so they do not measure identical work. The panel is useful for observing this demo, but it is not a rigorous benchmark. Results also depend on dataset size, warm-up, network and machine load, and whether both query paths do equivalent work."

**What would you improve next?**  
I’d resolve the database configuration mismatch, verify both engines produce consistent filter and facet behavior, add automated index synchronization, and benchmark with representative catalog data and query workloads.

## 8. Grounded details, assumptions, and things to verify

**Verified in this repository:** Symfony API, Vue/Vuex frontend, a product/category/brand catalog, MySQL-oriented full-text query code, Elasticsearch indexing and search services, sample-data seeding, API fallback logic, and a frontend timing comparison. The project plan and source files are the basis for the architecture described above.

**General engineering reasoning:** The synchronization risks, production reindex strategy, and benchmark limitations are standard concerns for systems with a database plus a search index; they are not evidence that this demo already implements those solutions.

**Verify before making a strong claim:** What “Anti-Gravity” means in this project; which database is actually configured locally; whether Elasticsearch is running with the expected mappings; whether the sample index contains seeded products; and how the two engines behave for representative queries. If you haven’t personally run a step, describe it as the intended flow and say you would verify it.
