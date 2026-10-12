<template>
  <FilterSection title="Min Rating">
    <label
      v-for="stars in [4, 3, 2, 1]"
      :key="stars"
      class="filter-item"
      :class="{ active: activeRating === stars }"
    >
      <input
        type="radio"
        name="rating"
        :checked="activeRating === stars"
        @change="select(stars)"
        class="filter-checkbox"
      />
      <span class="stars-row">
        <span v-for="s in 5" :key="s" class="star" :class="{ filled: s <= stars }">★</span>
      </span>
      <span class="filter-name">& up</span>
      <span class="filter-count">{{ countFor(stars) }}</span>
    </label>
    <label v-if="activeRating" class="filter-item">
      <input type="radio" name="rating" :checked="false" @change="clear" class="filter-checkbox" />
      <span class="filter-name" style="color: var(--color-text-muted)">Any rating</span>
    </label>
  </FilterSection>
</template>

<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'
import FilterSection from './FilterSection.vue'

const store = useStore()
const activeRating = computed(() => store.state.filters.active.rating_min)
const ratings      = computed(() => store.state.filters.facets.ratings)

function countFor(stars) {
  const match = ratings.value.filter(r => r.rating_floor >= stars)
  return match.reduce((sum, r) => sum + (r.doc_count ?? 0), 0) || ''
}

function select(stars) { store.dispatch('filters/setRatingMin', stars) }
function clear()       { store.dispatch('filters/setRatingMin', null) }
</script>

<style scoped>
@import './filter-item.css';

.stars-row { display: flex; gap: 1px; }

.star { font-size: 13px; color: var(--color-text-faint); transition: color var(--transition-fast); }
.star.filled { color: var(--color-warning); }
</style>
