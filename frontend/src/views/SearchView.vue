<template>
  <div class="search-view">
    <!-- Hero search bar -->
    <div class="search-hero" :class="{ 'search-hero--compact': hasResults }">
      <template v-if="!hasResults">
        <div class="hero-title">
          <span class="hero-gradient">Intelligent</span> Product Search
        </div>
        <p class="hero-sub">Compare MySQL FULLTEXT vs Elasticsearch in real-time</p>
      </template>
      <SearchBar />
      <div class="engine-selector">
        <label for="search-engine">Search with</label>
        <select id="search-engine" :value="engine" @change="changeEngine">
          <option value="both">Both engines</option>
          <option value="mysql">MySQL</option>
          <option value="es">Elasticsearch</option>
        </select>
      </div>
    </div>

    <!-- Main layout -->
    <div class="search-layout" v-if="hasResults || loading">
      <!-- Sidebar -->
      <FilterSidebar />

      <!-- Content -->
      <div class="search-content">
        <ResultsMeta />
        <SearchResults />
        <Pagination />
      </div>

      <!-- Right panel: timing -->
      <div class="timing-col">
        <TimingComparison />
      </div>
    </div>

    <!-- Initial empty state -->
    <div v-else-if="!loading" class="search-empty-home">
      <div class="feature-grid">
        <div class="feature-card">
          <div class="feature-icon">🗄️</div>
          <h3>MySQL FULLTEXT</h3>
          <p>Battle-tested full-text search with relevance scoring</p>
        </div>
        <div class="feature-card feature-card--highlight">
          <div class="feature-icon">⚡</div>
          <h3>Elasticsearch</h3>
          <p>Lightning-fast search with fuzzy matching, synonyms & autocomplete</p>
        </div>
        <div class="feature-card">
          <div class="feature-icon">📊</div>
          <h3>Live Comparison</h3>
          <p>See real timing differences between both engines on every search</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue'
import { useStore } from 'vuex'
import { useRoute, useRouter } from 'vue-router'
import SearchBar       from '../components/search/SearchBar.vue'
import FilterSidebar   from '../components/filters/FilterSidebar.vue'
import SearchResults   from '../components/search/SearchResults.vue'
import ResultsMeta     from '../components/search/ResultsMeta.vue'
import Pagination      from '../components/search/Pagination.vue'
import TimingComparison from '../components/comparison/TimingComparison.vue'

const store   = useStore()
const route   = useRoute()
const router  = useRouter()
const loading    = computed(() => store.state.search.loading)
const hasResults = computed(() => store.state.search.results.length > 0 || store.state.search.total > 0)
const engine     = computed(() => store.state.search.engine)

function normalizeEngine(value) {
  return ['mysql', 'es', 'both'].includes(value) ? value : 'both'
}

function changeEngine(event) {
  const selected = normalizeEngine(event.target.value)
  router.push({ query: { ...route.query, engine: selected, page: 1 } })
}

watch(() => route.query.engine, value => {
  const selected = normalizeEngine(value)
  if (selected === store.state.search.engine) return
  const query = typeof route.query.q === 'string' ? route.query.q : ''
  const routePage = Number.parseInt(route.query.page ?? '1', 10)
  const page = Number.isInteger(routePage) && routePage > 0 ? routePage : 1
  store.commit('search/SET_ENGINE', selected)
  store.dispatch('search/fetchResults', { engine: selected, query, page })
})

onMounted(() => {
  const q    = route.query.q    ?? ''
  const page = parseInt(route.query.page ?? '1', 10)
  const selectedEngine = normalizeEngine(route.query.engine)
  store.commit('search/SET_ENGINE', selectedEngine)
  if (route.query.engine !== selectedEngine) {
    router.replace({ query: { ...route.query, engine: selectedEngine } })
  }
  if (q) {
    store.commit('search/SET_QUERY', q)
    store.dispatch('search/fetchResults', { query: q, page, engine: selectedEngine })
  } else {
    // Load all products on first visit
    store.dispatch('search/fetchResults', { query: '', page: 1, engine: selectedEngine })
  }
})
</script>

<style scoped>
.search-view {
  max-width: var(--max-content);
  margin: 0 auto;
  padding: 0 var(--space-6);
}

/* Hero */
.search-hero {
  text-align: center;
  padding: var(--space-16) 0 var(--space-8);
  transition: all var(--transition-slow);
}

.search-hero--compact {
  padding: var(--space-6) 0 var(--space-4);
  text-align: left;
}

.hero-title {
  font-size: clamp(32px, 5vw, 56px);
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1.1;
  color: var(--color-text);
  margin-bottom: var(--space-3);
}

.hero-gradient {
  background: linear-gradient(135deg, #7c6bff 0%, #00d4aa 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-sub {
  font-size: 18px;
  color: var(--color-text-muted);
  margin-bottom: var(--space-8);
}

.engine-selector {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  margin: var(--space-3) auto 0;
  color: var(--color-text-muted);
  font-size: 13px;
}

.engine-selector select {
  padding: 7px 30px 7px 10px;
  color: var(--color-text);
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
}

.search-hero--compact .engine-selector {
  justify-content: flex-start;
}

/* Layout */
.search-layout {
  display: flex;
  gap: var(--space-6);
  align-items: flex-start;
  padding-bottom: var(--space-12);
}

.search-content {
  flex: 1;
  min-width: 0;
}

.timing-col {
  width: 340px;
  flex-shrink: 0;
  position: sticky;
  top: calc(var(--header-height) + var(--space-4));
}

/* Feature grid (home state) */
.search-empty-home {
  padding: var(--space-10) 0 var(--space-16);
}

.feature-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--space-6);
  max-width: 900px;
  margin: 0 auto;
}

.feature-card {
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: var(--space-8) var(--space-6);
  text-align: center;
  transition: all var(--transition-normal);
}

.feature-card:hover {
  border-color: var(--color-border-hover);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.feature-card--highlight {
  border-color: rgba(124, 107, 255, 0.3);
  background: linear-gradient(135deg, rgba(124,107,255,0.06), rgba(0,212,170,0.04));
}

.feature-icon {
  font-size: 40px;
  margin-bottom: var(--space-4);
}

.feature-card h3 {
  font-size: 18px;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: var(--space-2);
}

.feature-card p {
  font-size: 14px;
  color: var(--color-text-muted);
  line-height: 1.6;
}

/* Responsive */
@media (max-width: 1100px) {
  .timing-col { display: none; }
}

@media (max-width: 768px) {
  .search-layout { flex-direction: column; }
  .search-view { padding: 0 var(--space-4); }
}
</style>
