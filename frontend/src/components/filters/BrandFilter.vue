<template>
  <FilterSection title="Brands" :count="brands.length">
    <label
      v-for="brand in visibleBrands"
      :key="brand.id"
      class="filter-item"
      :class="{ active: isActive(brand.id) }"
    >
      <input
        type="checkbox"
        :checked="isActive(brand.id)"
        @change="toggle(brand.id)"
        class="filter-checkbox"
      />
      <span class="filter-name">{{ brand.name }}</span>
      <span class="filter-count">{{ brand.doc_count }}</span>
    </label>
    <button v-if="brands.length > 8 && !showAll" class="show-more" @click="showAll = true">
      +{{ brands.length - 8 }} more
    </button>
  </FilterSection>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useStore } from 'vuex'
import FilterSection from './FilterSection.vue'

const store = useStore()
const showAll = ref(false)

const brands = computed(() => store.state.filters.facets.brands)
const active = computed(() => store.state.filters.active.brand_id)
const visibleBrands = computed(() => showAll.value ? brands.value : brands.value.slice(0, 8))

function isActive(id) { return active.value.includes(id) }
function toggle(id)   { store.dispatch('filters/toggleBrand', id) }
</script>

<style scoped>
@import './filter-item.css';
</style>
