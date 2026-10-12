<template>
  <div class="search-bar-wrap">
    <div class="search-bar" :class="{ focused }">
      <!-- Search icon -->
      <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="11" cy="11" r="8" /><path d="m21 21-4.35-4.35" />
      </svg>

      <input
        id="search-input"
        ref="inputRef"
        v-model="localQuery"
        type="search"
        autocomplete="off"
        spellcheck="false"
        placeholder="Search products, brands, categories…"
        @input="onInput"
        @keydown="onKeydown"
        @focus="focused = true"
        @blur="onBlur"
      />

      <!-- Clear button -->
      <button v-if="localQuery" class="clear-btn" @click="clearQuery" title="Clear" id="clear-search-btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18" /><line x1="6" y1="6" x2="18" y2="18" />
        </svg>
      </button>

      <!-- Search button -->
      <button class="search-btn" @click="doSearch" id="search-submit-btn">
        Search
      </button>
    </div>

    <!-- Autocomplete Dropdown -->
    <AutocompleteDropdown
      v-if="showAutocomplete"
      :products="autocomplete.products"
      :categories="autocomplete.categories"
      :loading="autocomplete.loading"
      :highlight="localQuery"
      :selected-index="selectedIndex"
      @select="onAutocompleteSelect"
      @close="showAutocomplete = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useStore } from 'vuex'
import { useRouter, useRoute } from 'vue-router'
import AutocompleteDropdown from './AutocompleteDropdown.vue'

const store  = useStore()
const router = useRouter()
const route  = useRoute()

const inputRef      = ref(null)
const localQuery    = ref(route.query.q ?? '')
const focused       = ref(false)
const showAutocomplete = ref(false)
const selectedIndex = ref(-1)

let debounceTimer = null

const autocomplete = computed(() => store.state.search.autocomplete)
const allSuggestions = computed(() => [
  ...autocomplete.value.products,
  ...autocomplete.value.categories,
])

function onInput() {
  selectedIndex.value = -1
  clearTimeout(debounceTimer)
  if (localQuery.value.length < 2) {
    showAutocomplete.value = false
    return
  }
  debounceTimer = setTimeout(() => {
    store.dispatch('search/fetchAutocomplete', localQuery.value)
    showAutocomplete.value = true
  }, 300)
}

function onKeydown(e) {
  if (!showAutocomplete.value) {
    if (e.key === 'Enter') doSearch()
    return
  }
  const max = allSuggestions.value.length
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    selectedIndex.value = (selectedIndex.value + 1) % max
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    selectedIndex.value = (selectedIndex.value - 1 + max) % max
  } else if (e.key === 'Enter') {
    e.preventDefault()
    if (selectedIndex.value >= 0) {
      onAutocompleteSelect(allSuggestions.value[selectedIndex.value])
    } else {
      doSearch()
    }
  } else if (e.key === 'Escape') {
    showAutocomplete.value = false
    selectedIndex.value = -1
  }
}

function onBlur() {
  focused.value = false
  setTimeout(() => { showAutocomplete.value = false }, 150)
}

function doSearch() {
  showAutocomplete.value = false
  const q = localQuery.value.trim()
  router.push({ path: '/', query: { q, page: 1, engine: store.state.search.engine } })
  store.dispatch('search/fetchResults', { query: q, page: 1 })
}

function onAutocompleteSelect(item) {
  if (item.slug && !item.price) {
    // Category
    localQuery.value = item.name
    doSearch()
  } else {
    // Product
    router.push(`/product/${item.slug}`)
  }
  showAutocomplete.value = false
}

function clearQuery() {
  localQuery.value = ''
  store.commit('search/CLEAR_AUTOCOMPLETE')
  showAutocomplete.value = false
  store.dispatch('search/fetchResults', { query: '', page: 1 })
  router.push({ path: '/', query: { engine: store.state.search.engine } })
  inputRef.value?.focus()
}

// Sync with URL
watch(() => route.query.q, q => { if (q !== undefined) localQuery.value = q ?? '' })
</script>

<style scoped>
.search-bar-wrap {
  position: relative;
  width: 100%;
}

.search-bar {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  background: var(--color-surface-2);
  border: 1.5px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: 0 var(--space-2) 0 var(--space-4);
  transition: all var(--transition-normal);
  height: 52px;
}

.search-bar.focused {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(124, 107, 255, 0.12), var(--shadow-md);
  background: var(--color-surface-3);
}

.search-icon {
  color: var(--color-text-muted);
  flex-shrink: 0;
  transition: color var(--transition-fast);
}

.search-bar.focused .search-icon {
  color: var(--color-primary);
}

input {
  flex: 1;
  background: none;
  border: none;
  outline: none;
  color: var(--color-text);
  font-size: 16px;
  font-weight: 400;
  min-width: 0;
}

input::placeholder {
  color: var(--color-text-muted);
}

input::-webkit-search-cancel-button { display: none; }

.clear-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: var(--radius-sm);
  color: var(--color-text-muted);
  flex-shrink: 0;
  transition: all var(--transition-fast);
}

.clear-btn:hover {
  background: var(--color-surface-3);
  color: var(--color-text);
}

.search-btn {
  flex-shrink: 0;
  padding: 0 var(--space-5);
  height: 36px;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));
  color: #fff;
  border-radius: var(--radius-md);
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 0.01em;
  transition: all var(--transition-fast);
  box-shadow: 0 2px 8px rgba(124, 107, 255, 0.35);
}

.search-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 16px rgba(124, 107, 255, 0.45);
}

.search-btn:active {
  transform: translateY(0);
}
</style>
