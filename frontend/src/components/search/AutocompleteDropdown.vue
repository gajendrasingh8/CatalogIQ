<template>
  <div class="autocomplete" id="autocomplete-dropdown">
    <!-- Loading state -->
    <div v-if="loading" class="ac-loading">
      <div class="spinner-sm"></div>
      <span>Searching…</span>
    </div>

    <template v-else>
      <!-- Products -->
      <div v-if="products.length" class="ac-section">
        <div class="ac-section-label">Products</div>
        <button
          v-for="(item, i) in products"
          :key="'p' + i"
          class="ac-item"
          :class="{ active: selectedIndex === i }"
          @mousedown.prevent="$emit('select', item)"
        >
          <img v-if="item.image_url" :src="item.image_url" :alt="item.name" class="ac-img" />
          <div v-else class="ac-img-placeholder">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="3" width="18" height="18" rx="2"/><path d="m21 15-5-5L5 21"/><circle cx="8.5" cy="8.5" r="1.5"/>
            </svg>
          </div>
          <div class="ac-item-info">
            <span class="ac-item-name" v-html="highlightText(item.name)"></span>
            <span v-if="item.price" class="ac-item-price">${{ parseFloat(item.price).toFixed(2) }}</span>
          </div>
          <div class="ac-arrow">→</div>
        </button>
      </div>

      <!-- Categories -->
      <div v-if="categories.length" class="ac-section">
        <div class="ac-section-label">Categories</div>
        <button
          v-for="(cat, i) in categories"
          :key="'c' + i"
          class="ac-item"
          :class="{ active: selectedIndex === products.length + i }"
          @mousedown.prevent="$emit('select', cat)"
        >
          <div class="ac-cat-icon">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 6h18M3 12h18M3 18h18"/>
            </svg>
          </div>
          <span class="ac-item-name" v-html="highlightText(cat.name)"></span>
        </button>
      </div>

      <!-- Empty -->
      <div v-if="!products.length && !categories.length" class="ac-empty">
        No suggestions found
      </div>
    </template>
  </div>
</template>

<script setup>
defineProps({
  products:      { type: Array, default: () => [] },
  categories:    { type: Array, default: () => [] },
  loading:       { type: Boolean, default: false },
  highlight:     { type: String, default: '' },
  selectedIndex: { type: Number, default: -1 },
})

defineEmits(['select', 'close'])

function highlightText(text) {
  if (!text) return ''
  // Escape special regex chars in highlight term
  // then wrap matches with <mark>
  const escaped = text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
  return text.replace(new RegExp(`(${escaped})`, 'gi'), '<mark>$1</mark>')
}
</script>

<style scoped>
.autocomplete {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  right: 0;
  background: var(--color-surface-2);
  border: 1px solid var(--color-border-hover);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  z-index: 200;
  overflow: hidden;
  animation: fadeIn 0.15s ease;
  max-height: 440px;
  overflow-y: auto;
}

.ac-loading {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-4) var(--space-5);
  color: var(--color-text-muted);
  font-size: 14px;
}

.spinner-sm {
  width: 16px;
  height: 16px;
  border: 2px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

.ac-section { padding: var(--space-2) 0; }

.ac-section + .ac-section {
  border-top: 1px solid var(--color-border);
}

.ac-section-label {
  padding: var(--space-2) var(--space-4);
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--color-text-faint);
}

.ac-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-2) var(--space-4);
  text-align: left;
  color: var(--color-text);
  transition: background var(--transition-fast);
  cursor: pointer;
}

.ac-item:hover,
.ac-item.active {
  background: var(--color-surface-3);
}

.ac-img {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-sm);
  object-fit: cover;
  flex-shrink: 0;
  background: var(--color-surface-3);
}

.ac-img-placeholder {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-sm);
  background: var(--color-surface-3);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-faint);
  flex-shrink: 0;
}

.ac-cat-icon {
  width: 36px;
  height: 36px;
  border-radius: var(--radius-sm);
  background: rgba(124, 107, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-primary);
  flex-shrink: 0;
}

.ac-item-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.ac-item-name {
  font-size: 14px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ac-item-name :deep(mark) {
  background: rgba(124, 107, 255, 0.3);
  color: #a89eff;
  border-radius: 2px;
  padding: 0 1px;
}

.ac-item-price {
  font-size: 12px;
  color: var(--color-accent);
  font-weight: 600;
}

.ac-arrow {
  color: var(--color-text-faint);
  font-size: 14px;
  flex-shrink: 0;
}

.ac-empty {
  padding: var(--space-5);
  text-align: center;
  color: var(--color-text-muted);
  font-size: 14px;
}
</style>
