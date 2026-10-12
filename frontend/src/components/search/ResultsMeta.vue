<template>
  <div class="results-meta" id="results-meta">
    <div class="meta-left">
      <span v-if="loading" class="meta-loading">
        <span class="spinner-dots"><span></span><span></span><span></span></span>
        Searching…
      </span>
      <template v-else>
        <span class="meta-count">
          <strong>{{ total.toLocaleString() }}</strong> result{{ total !== 1 ? 's' : '' }}
        </span>
        <span v-if="query" class="meta-query">for "<em>{{ query }}</em>"</span>
        <span v-if="resultEngine" class="meta-engine-badge" :class="resultEngine === 'elasticsearch' ? 'badge--es' : 'badge--mysql'">
          {{ resultEngine === 'elasticsearch' ? '⚡ Elasticsearch' : isFallback ? '🗄 MySQL fallback' : '🗄 MySQL' }}
        </span>
      </template>
    </div>

    <!-- Sort -->
    <div class="meta-right">
      <label for="sort-select" class="sort-label">Sort:</label>
      <select id="sort-select" :value="sort" @change="changeSort" class="sort-select">
        <option value="relevance">Relevance</option>
        <option value="price_asc">Price: Low → High</option>
        <option value="price_desc">Price: High → Low</option>
        <option value="rating">Top Rated</option>
        <option value="newest">Newest</option>
      </select>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'

const store   = useStore()
const loading = computed(() => store.state.search.loading)
const total   = computed(() => store.state.search.total)
const query   = computed(() => store.state.search.query)
const sort    = computed(() => store.state.search.sort)
const resultEngine = computed(() => store.state.search.resultEngine)
const isFallback = computed(() => resultEngine.value === 'mysql' && store.state.search.engine !== 'mysql')

function changeSort(e) {
  store.dispatch('search/fetchResults', { sort: e.target.value, page: 1 })
}
</script>

<style scoped>
.results-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: var(--space-3);
  padding: var(--space-3) 0;
  border-bottom: 1px solid var(--color-border);
  min-height: 44px;
}

.meta-left {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.meta-loading {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  color: var(--color-text-muted);
  font-size: 14px;
}

.spinner-dots {
  display: flex;
  gap: 4px;
}

.spinner-dots span {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: var(--color-primary);
  animation: bounce 1.2s infinite ease-in-out;
}

.spinner-dots span:nth-child(2) { animation-delay: 0.2s; }
.spinner-dots span:nth-child(3) { animation-delay: 0.4s; }

@keyframes bounce {
  0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
  40%            { transform: scale(1);   opacity: 1; }
}

.meta-count {
  font-size: 15px;
  color: var(--color-text);
}

.meta-query {
  font-size: 14px;
  color: var(--color-text-muted);
}

.meta-query em {
  font-style: normal;
  color: var(--color-text);
}

.meta-engine-badge {
  padding: 2px 8px;
  border-radius: var(--radius-full);
  font-size: 11px;
  font-weight: 600;
}

.badge--es {
  background: rgba(16, 185, 129, 0.12);
  color: #34d399;
  border: 1px solid rgba(16, 185, 129, 0.25);
}

.badge--mysql {
  background: rgba(245, 158, 11, 0.12);
  color: #fbbf24;
  border: 1px solid rgba(245, 158, 11, 0.25);
}

.meta-right {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.sort-label {
  font-size: 13px;
  color: var(--color-text-muted);
}

.sort-select {
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  color: var(--color-text);
  font-size: 13px;
  padding: 6px 10px;
  cursor: pointer;
  outline: none;
  transition: border-color var(--transition-fast);
}

.sort-select:focus {
  border-color: var(--color-primary);
}
</style>
