<template>
  <nav v-if="totalPages > 1" class="pagination" id="pagination" aria-label="Page navigation">
    <!-- Prev -->
    <button
      class="page-btn page-btn--nav"
      :disabled="page <= 1"
      @click="go(page - 1)"
      id="prev-page-btn"
    >
      ← Prev
    </button>

    <!-- Page numbers -->
    <template v-for="p in pages" :key="p">
      <span v-if="p === '...'" class="page-ellipsis">…</span>
      <button
        v-else
        class="page-btn"
        :class="{ active: p === page }"
        @click="go(p)"
        :id="`page-btn-${p}`"
      >
        {{ p }}
      </button>
    </template>

    <!-- Next -->
    <button
      class="page-btn page-btn--nav"
      :disabled="page >= totalPages"
      @click="go(page + 1)"
      id="next-page-btn"
    >
      Next →
    </button>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'
import { useRouter, useRoute } from 'vue-router'

const store  = useStore()
const router = useRouter()
const route  = useRoute()

const page       = computed(() => store.state.search.page)
const totalPages = computed(() => store.getters['search/totalPages'])

// Smart page range with ellipsis
const pages = computed(() => {
  const total = totalPages.value
  const cur   = page.value
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

  const range = []
  if (cur > 3)         range.push(1, '...')
  else                 range.push(1, 2, 3)

  for (let p = Math.max(2, cur - 1); p <= Math.min(total - 1, cur + 1); p++) {
    if (!range.includes(p)) range.push(p)
  }

  if (cur < total - 2) range.push('...', total)
  else                 range.push(total - 1, total)

  return [...new Set(range)]
})

function go(p) {
  if (p < 1 || p > totalPages.value) return
  router.push({ query: { ...route.query, page: p } })
  store.dispatch('search/fetchResults', { page: p })
  window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<style scoped>
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-1);
  padding: var(--space-6) 0;
  flex-wrap: wrap;
}

.page-btn {
  min-width: 36px;
  height: 36px;
  padding: 0 var(--space-3);
  border-radius: var(--radius-md);
  font-size: 13px;
  font-weight: 500;
  color: var(--color-text-muted);
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  transition: all var(--transition-fast);
}

.page-btn:hover:not(:disabled):not(.active) {
  background: var(--color-surface-3);
  color: var(--color-text);
  border-color: var(--color-border-hover);
}

.page-btn.active {
  background: var(--color-primary);
  color: #fff;
  border-color: var(--color-primary);
  font-weight: 700;
  box-shadow: 0 2px 8px rgba(124, 107, 255, 0.4);
}

.page-btn--nav {
  padding: 0 var(--space-4);
  font-size: 13px;
}

.page-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.page-ellipsis {
  padding: 0 var(--space-2);
  color: var(--color-text-faint);
  font-size: 14px;
}
</style>
