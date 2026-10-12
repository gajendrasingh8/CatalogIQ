<template>
  <div v-if="pills.length" class="active-filters">
    <button
      v-for="pill in pills"
      :key="pill.key"
      class="filter-pill"
      @click="pill.remove()"
    >
      {{ pill.label }}
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'

const store = useStore()

const active = computed(() => store.state.filters.active)
const facets = computed(() => store.state.filters.facets)

const pills = computed(() => {
  const out = []

  active.value.category_id.forEach(id => {
    const cat = facets.value.categories.find(c => c.id == id || c.id === id)
    out.push({
      key:    'cat-' + id,
      label:  cat?.name ?? 'Category ' + id,
      remove: () => store.dispatch('filters/toggleCategory', id),
    })
  })

  active.value.brand_id.forEach(id => {
    const br = facets.value.brands.find(b => b.id == id || b.id === id)
    out.push({
      key:    'br-' + id,
      label:  br?.name ?? 'Brand ' + id,
      remove: () => store.dispatch('filters/toggleBrand', id),
    })
  })

  if (active.value.price_min != null || active.value.price_max != null) {
    const mn = active.value.price_min ?? 0
    const mx = active.value.price_max ?? '∞'
    out.push({
      key:    'price',
      label:  `$${mn} – $${mx}`,
      remove: () => store.dispatch('filters/setPriceRange', { min: null, max: null }),
    })
  }

  if (active.value.rating_min != null) {
    out.push({
      key:    'rating',
      label:  `⭐ ${active.value.rating_min}+`,
      remove: () => store.dispatch('filters/setRatingMin', null),
    })
  }

  return out
})
</script>

<style scoped>
.active-filters {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
  padding-bottom: var(--space-3);
}

.filter-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  background: rgba(124, 107, 255, 0.15);
  border: 1px solid rgba(124, 107, 255, 0.3);
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 500;
  color: #a89eff;
  transition: all var(--transition-fast);
}

.filter-pill:hover {
  background: rgba(124, 107, 255, 0.25);
  border-color: rgba(124, 107, 255, 0.5);
}
</style>
