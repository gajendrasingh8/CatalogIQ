<template>
  <FilterSection title="Categories" :count="categories.length">
    <label
      v-for="cat in visibleCategories"
      :key="cat.id"
      class="filter-item"
      :class="{ active: isActive(cat.id) }"
    >
      <input
        type="checkbox"
        :checked="isActive(cat.id)"
        @change="toggle(cat.id)"
        class="filter-checkbox"
      />
      <span class="filter-name">{{ cat.name }}</span>
      <span class="filter-count">{{ cat.doc_count }}</span>
    </label>
    <button v-if="categories.length > 8 && !showAll" class="show-more" @click="showAll = true">
      +{{ categories.length - 8 }} more
    </button>
  </FilterSection>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useStore } from 'vuex'
import FilterSection from './FilterSection.vue'

const store = useStore()
const showAll = ref(false)

const categories = computed(() => store.state.filters.facets.categories)
const active = computed(() => store.state.filters.active.category_id)
const visibleCategories = computed(() => showAll.value ? categories.value : categories.value.slice(0, 8))

function isActive(id) { return active.value.includes(id) }
function toggle(id)   { store.dispatch('filters/toggleCategory', id) }
</script>

<style scoped>
@import './filter-item.css';
</style>
