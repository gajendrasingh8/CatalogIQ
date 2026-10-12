<template>
  <FilterSection title="Price Range">
    <div class="price-ranges">
      <label
        v-for="range in priceRanges"
        :key="range.price_range"
        class="filter-item"
        :class="{ active: isActive(range) }"
      >
        <input
          type="radio"
          name="price_range"
          :checked="isActive(range)"
          @change="select(range)"
          class="filter-checkbox"
        />
        <span class="filter-name">{{ range.price_range }}</span>
        <span class="filter-count">{{ range.doc_count }}</span>
      </label>
      <label v-if="hasActivePrice" class="filter-item">
        <input type="radio" name="price_range" :checked="false" @change="clear" class="filter-checkbox" />
        <span class="filter-name" style="color: var(--color-text-muted)">Any price</span>
      </label>
    </div>
  </FilterSection>
</template>

<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'
import FilterSection from './FilterSection.vue'

const store = useStore()
const priceRanges = computed(() => store.state.filters.facets.price_ranges)
const active      = computed(() => store.state.filters.active)
const hasActivePrice = computed(() => active.value.price_min != null || active.value.price_max != null)

function isActive(range) {
  return active.value.price_min == range.min_price && active.value.price_max == range.max_price
}

function select(range) {
  store.dispatch('filters/setPriceRange', { min: range.min_price, max: range.max_price === 99999 ? null : range.max_price })
}

function clear() {
  store.dispatch('filters/setPriceRange', { min: null, max: null })
}
</script>

<style scoped>
@import './filter-item.css';
</style>
