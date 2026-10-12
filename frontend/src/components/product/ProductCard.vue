<template>
  <router-link :to="`/product/${product.slug}`" class="product-card" :id="`product-card-${product.id}`">
    <!-- Image -->
    <div class="card-img-wrap">
      <img
        v-if="imageUrl && !imgError"
        :src="imageUrl"
        :alt="product.name"
        class="card-img"
        loading="lazy"
        @error="imgError = true"
      />
      <div v-else class="card-img-placeholder" :style="placeholderStyle">
        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" opacity="0.5">
          <rect x="3" y="3" width="18" height="18" rx="2"/><path d="m21 15-5-5L5 21"/><circle cx="8.5" cy="8.5" r="1.5"/>
        </svg>
        <span class="placeholder-label">{{ shortName }}</span>
      </div>

      <!-- Stock badge overlay -->
      <div class="card-stock-overlay">
        <StockBadge :status="product.stock_status" />
      </div>

      <!-- Discount badge -->
      <div v-if="discountPct" class="discount-badge">-{{ discountPct }}%</div>
    </div>

    <!-- Body -->
    <div class="card-body">
      <!-- Category + Brand -->
      <div class="card-meta">
        <span class="card-category">{{ product.category?.name ?? product.category_name ?? '' }}</span>
        <span class="card-dot">·</span>
        <span class="card-brand">{{ product.brand?.name ?? product.brand_name ?? '' }}</span>
      </div>

      <!-- Name with highlight support -->
      <h3
        class="card-name"
        v-html="product._highlight?.name?.[0] ?? product.name"
      ></h3>

      <!-- Star rating -->
      <StarRating :rating="parseFloat(product.rating)" :count="product.review_count" />

      <!-- Price -->
      <div class="card-price-row">
        <PriceBadge :price="product.price" :original="product.original_price ?? product.originalPrice" />
      </div>
    </div>
  </router-link>
</template>

<script setup>
import { computed, ref } from 'vue'
import StarRating from '../ui/StarRating.vue'
import PriceBadge from '../ui/PriceBadge.vue'
import StockBadge from '../ui/StockBadge.vue'

const props = defineProps({ product: { type: Object, required: true } })
const imgError = ref(false)

const discountPct = computed(() => {
  const orig = parseFloat(props.product.original_price ?? props.product.originalPrice)
  const curr = parseFloat(props.product.price)
  if (!orig || orig <= curr) return null
  return Math.round((1 - curr / orig) * 100)
})

// Handle both snake_case (Elasticsearch) and camelCase (Symfony serializer / MySQL)
const imageUrl = computed(() => props.product.image_url ?? props.product.imageUrl ?? null)

// Short product name for placeholder label
const shortName = computed(() => {
  const name = props.product.name ?? ''
  return name.length > 22 ? name.slice(0, 22) + '…' : name
})

// Category-based gradient background for placeholder
const CATEGORY_GRADIENTS = {
  'smartphones': 'linear-gradient(135deg, #1e3a5f 0%, #2d6a9f 100%)',
  'laptops':     'linear-gradient(135deg, #1a1a2e 0%, #16213e 100%)',
  'headphones':  'linear-gradient(135deg, #2d1b69 0%, #5c3da3 100%)',
  'tablets':     'linear-gradient(135deg, #0d3b2e 0%, #1a6b52 100%)',
  'smartwatches':'linear-gradient(135deg, #3b1f0d 0%, #8b4a13 100%)',
  'cameras':     'linear-gradient(135deg, #1a0d2e 0%, #4a1a6e 100%)',
  'speakers':    'linear-gradient(135deg, #0d2e1a 0%, #1a6b3a 100%)',
  'gaming':      'linear-gradient(135deg, #1a0d0d 0%, #6e1a1a 100%)',
  'accessories': 'linear-gradient(135deg, #1f2d3b 0%, #3d5a6e 100%)',
  'monitors':    'linear-gradient(135deg, #0d1a2e 0%, #1a3a6e 100%)',
}

const placeholderStyle = computed(() => {
  const catName = (props.product.category?.name ?? props.product.category_name ?? '').toLowerCase()
  const gradient = CATEGORY_GRADIENTS[catName] ?? 'linear-gradient(135deg, #1e1e2e 0%, #2d2d4e 100%)'
  return { background: gradient }
})
</script>

<style scoped>
.product-card {
  display: flex;
  flex-direction: column;
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
  transition: all var(--transition-normal);
  cursor: pointer;
  text-decoration: none;
  position: relative;
  animation: fadeIn 0.3s ease;
}

.product-card:hover {
  border-color: rgba(124, 107, 255, 0.35);
  transform: translateY(-3px);
  box-shadow: 0 12px 32px rgba(0,0,0,0.4), 0 0 0 1px rgba(124,107,255,0.15);
}

/* Image */
.card-img-wrap {
  position: relative;
  aspect-ratio: 4/3;
  background: var(--color-surface-3);
  overflow: hidden;
}

.card-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--transition-slow);
}

.product-card:hover .card-img {
  transform: scale(1.04);
}

.card-img-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: rgba(255,255,255,0.5);
  background: var(--color-surface-3);
}

.placeholder-label {
  font-size: 10px;
  font-weight: 500;
  color: rgba(255,255,255,0.4);
  text-align: center;
  padding: 0 8px;
  line-height: 1.3;
}

.card-stock-overlay {
  position: absolute;
  bottom: var(--space-2);
  left: var(--space-2);
}

.discount-badge {
  position: absolute;
  top: var(--space-2);
  right: var(--space-2);
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: var(--radius-sm);
}

/* Body */
.card-body {
  padding: var(--space-3) var(--space-4) var(--space-4);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  flex: 1;
}

.card-meta {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: var(--color-text-faint);
}

.card-dot { color: var(--color-text-faint); }

.card-name {
  font-size: 14px;
  font-weight: 600;
  color: var(--color-text);
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-name :deep(em) {
  font-style: normal;
  background: rgba(124, 107, 255, 0.25);
  color: #a89eff;
  border-radius: 2px;
  padding: 0 2px;
}

.card-price-row {
  margin-top: auto;
  padding-top: var(--space-2);
}
</style>
