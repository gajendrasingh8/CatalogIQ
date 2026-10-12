<template>
  <aside class="filter-sidebar" id="filter-sidebar">
    <!-- Header -->
    <div class="sidebar-header">
      <h2 class="sidebar-title">Filters</h2>
      <button
        v-if="hasActiveFilters"
        class="clear-all-btn"
        @click="clearAll"
        id="clear-all-filters-btn"
      >
        Clear all ({{ activeCount }})
      </button>
    </div>

    <!-- Active filter pills -->
    <ActiveFilters />

    <!-- Category Filter -->
    <CategoryFilter />

    <!-- Brand Filter -->
    <BrandFilter />

    <!-- Price Range -->
    <PriceRangeFilter />

    <!-- Rating -->
    <RatingFilter />
  </aside>
</template>

<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'
import ActiveFilters    from './ActiveFilters.vue'
import CategoryFilter   from './CategoryFilter.vue'
import BrandFilter      from './BrandFilter.vue'
import PriceRangeFilter from './PriceRangeFilter.vue'
import RatingFilter     from './RatingFilter.vue'

const store = useStore()
const hasActiveFilters = computed(() => store.getters['filters/hasActiveFilters'])
const activeCount      = computed(() => store.getters['filters/activeFilterCount'])

function clearAll() {
  store.dispatch('filters/clearAllFilters')
}
</script>

<style scoped>
.filter-sidebar {
  width: var(--sidebar-width);
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.sidebar-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-2) 0 var(--space-3);
}

.sidebar-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--color-text);
  letter-spacing: -0.01em;
}

.clear-all-btn {
  font-size: 12px;
  color: var(--color-primary);
  font-weight: 500;
  padding: 3px 8px;
  border-radius: var(--radius-sm);
  transition: background var(--transition-fast);
}

.clear-all-btn:hover {
  background: rgba(124, 107, 255, 0.12);
}
</style>
