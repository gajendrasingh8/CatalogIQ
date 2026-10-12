<template>
  <span class="stock-badge" :class="cls">{{ label }}</span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ status: { type: String, default: 'in_stock' } })

const cls = computed(() => ({
  'stock--in':  props.status === 'in_stock',
  'stock--low': props.status === 'low_stock',
  'stock--out': props.status === 'out_of_stock',
}))

const label = computed(() => ({
  in_stock:     'In Stock',
  low_stock:    'Low Stock',
  out_of_stock: 'Out of Stock',
}[props.status] ?? props.status))
</script>

<style scoped>
.stock-badge {
  display: inline-block;
  padding: 2px 7px;
  border-radius: var(--radius-full);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.stock--in  { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
.stock--low { background: rgba(245,158,11,0.15);  color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
.stock--out { background: rgba(239,68,68,0.15);   color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
</style>
