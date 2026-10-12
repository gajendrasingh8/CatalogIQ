<template>
  <div class="price-badge">
    <span class="price-current">${{ parseFloat(price).toFixed(2) }}</span>
    <span v-if="showOriginal" class="price-original">${{ parseFloat(original).toFixed(2) }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  price:    { type: [String, Number], required: true },
  original: { type: [String, Number], default: null },
})

const showOriginal = computed(() => {
  if (!props.original) return false
  return parseFloat(props.original) > parseFloat(props.price)
})
</script>

<style scoped>
.price-badge {
  display: flex;
  align-items: baseline;
  gap: var(--space-2);
}

.price-current {
  font-size: 18px;
  font-weight: 700;
  color: var(--color-accent);
  font-family: var(--font-mono);
  letter-spacing: -0.02em;
}

.price-original {
  font-size: 13px;
  color: var(--color-text-faint);
  text-decoration: line-through;
  font-family: var(--font-mono);
}
</style>
