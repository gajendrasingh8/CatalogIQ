<template>
  <div class="product-view">
    <!-- Back -->
    <router-link to="/" class="back-link" id="back-to-search">
      ← Back to results
    </router-link>

    <!-- Loading -->
    <LoadingSpinner v-if="loading" label="Loading product…" />

    <!-- Error -->
    <div v-else-if="error" class="pd-error">
      <p>{{ error }}</p>
      <router-link to="/" class="btn-primary">Back to Search</router-link>
    </div>

    <!-- Product Detail -->
    <div v-else-if="product" class="product-detail" id="product-detail">
      <div class="pd-left">
        <!-- Image -->
        <div class="pd-img-wrap">
          <img v-if="product.imageUrl || product.image_url"
            :src="product.imageUrl ?? product.image_url"
            :alt="product.name"
            class="pd-img"
          />
          <div v-else class="pd-img-placeholder">
            <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
              <rect x="3" y="3" width="18" height="18" rx="2"/><path d="m21 15-5-5L5 21"/><circle cx="8.5" cy="8.5" r="1.5"/>
            </svg>
          </div>
        </div>
      </div>

      <div class="pd-right">
        <!-- Breadcrumb -->
        <div class="pd-breadcrumb">
          <span>{{ product.category?.name ?? product.category_name }}</span>
          <span>›</span>
          <span>{{ product.brand?.name ?? product.brand_name }}</span>
        </div>

        <h1 class="pd-name">{{ product.name }}</h1>

        <!-- SKU -->
        <div v-if="product.sku" class="pd-sku">SKU: <code>{{ product.sku }}</code></div>

        <!-- Rating & stock row -->
        <div class="pd-meta-row">
          <StarRating :rating="parseFloat(product.rating)" :count="product.reviewCount ?? product.review_count" />
          <StockBadge :status="product.stockStatus ?? product.stock_status" />
        </div>

        <!-- Price -->
        <div class="pd-price-block">
          <PriceBadge :price="product.price" :original="product.originalPrice ?? product.original_price" />
          <span v-if="discountPct" class="pd-discount">Save {{ discountPct }}%</span>
        </div>

        <!-- Description -->
        <p v-if="product.description" class="pd-description">{{ product.description }}</p>

        <!-- Tags -->
        <div v-if="product.tags?.length" class="pd-tags">
          <span v-for="tag in product.tags" :key="tag" class="pd-tag">{{ tag }}</span>
        </div>

        <!-- Specifications -->
        <div v-if="specs.length" class="pd-specs">
          <h3 class="pd-specs-title">Specifications</h3>
          <div class="specs-grid">
            <div v-for="[k, v] in specs" :key="k" class="spec-row">
              <span class="spec-key">{{ k }}</span>
              <span class="spec-val">{{ v }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRoute } from 'vue-router'
import LoadingSpinner from '../components/ui/LoadingSpinner.vue'
import StarRating     from '../components/ui/StarRating.vue'
import PriceBadge     from '../components/ui/PriceBadge.vue'
import StockBadge     from '../components/ui/StockBadge.vue'

const store   = useStore()
const route   = useRoute()

const product = computed(() => store.state.products.current)
const loading = computed(() => store.state.products.loading)
const error   = computed(() => store.state.products.error)

const discountPct = computed(() => {
  const orig = parseFloat(product.value?.originalPrice ?? product.value?.original_price)
  const curr = parseFloat(product.value?.price)
  if (!orig || orig <= curr) return null
  return Math.round((1 - curr / orig) * 100)
})

const specs = computed(() => {
  const s = product.value?.specifications
  if (!s || typeof s !== 'object') return []
  return Object.entries(s)
})

onMounted(() => {
  store.dispatch('products/fetchProduct', route.params.slug)
})
</script>

<style scoped>
.product-view {
  max-width: var(--max-content);
  margin: 0 auto;
  padding: var(--space-6);
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  color: var(--color-text-muted);
  font-size: 14px;
  margin-bottom: var(--space-6);
  transition: color var(--transition-fast);
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-sm);
}

.back-link:hover {
  color: var(--color-primary);
  background: rgba(124, 107, 255, 0.08);
}

/* Product detail layout */
.product-detail {
  display: grid;
  grid-template-columns: 440px 1fr;
  gap: var(--space-10);
  animation: fadeIn 0.3s ease;
}

/* Left */
.pd-img-wrap {
  border-radius: var(--radius-xl);
  overflow: hidden;
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  aspect-ratio: 1;
}

.pd-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.pd-img-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--color-text-faint);
}

/* Right */
.pd-breadcrumb {
  display: flex;
  gap: var(--space-2);
  font-size: 13px;
  color: var(--color-text-muted);
  margin-bottom: var(--space-3);
}

.pd-name {
  font-size: clamp(22px, 3vw, 36px);
  font-weight: 800;
  letter-spacing: -0.02em;
  line-height: 1.2;
  color: var(--color-text);
  margin-bottom: var(--space-3);
}

.pd-sku {
  font-size: 12px;
  color: var(--color-text-faint);
  margin-bottom: var(--space-3);
}

.pd-sku code {
  font-family: var(--font-mono);
  background: var(--color-surface-3);
  padding: 2px 6px;
  border-radius: var(--radius-sm);
}

.pd-meta-row {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  margin-bottom: var(--space-4);
  flex-wrap: wrap;
}

.pd-price-block {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  margin-bottom: var(--space-6);
}

.pd-discount {
  padding: 4px 10px;
  background: rgba(239,68,68,0.15);
  color: #f87171;
  border: 1px solid rgba(239,68,68,0.3);
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 700;
}

.pd-description {
  font-size: 15px;
  color: var(--color-text-muted);
  line-height: 1.7;
  margin-bottom: var(--space-5);
}

/* Tags */
.pd-tags {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
  margin-bottom: var(--space-6);
}

.pd-tag {
  padding: 4px 10px;
  background: var(--color-surface-3);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-full);
  font-size: 12px;
  color: var(--color-text-muted);
}

/* Specs */
.pd-specs { margin-top: var(--space-4); }

.pd-specs-title {
  font-size: 14px;
  font-weight: 700;
  color: var(--color-text-muted);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: var(--space-3);
}

.specs-grid {
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.spec-row {
  display: grid;
  grid-template-columns: 180px 1fr;
  padding: var(--space-3) var(--space-4);
  border-bottom: 1px solid var(--color-border);
  font-size: 14px;
}

.spec-row:last-child { border-bottom: none; }

.spec-key { color: var(--color-text-muted); font-weight: 500; }
.spec-val { color: var(--color-text); }

/* Error */
.pd-error {
  text-align: center;
  padding: var(--space-12);
}

.btn-primary {
  display: inline-block;
  margin-top: var(--space-4);
  padding: var(--space-3) var(--space-6);
  background: var(--color-primary);
  color: #fff;
  border-radius: var(--radius-md);
  font-weight: 600;
}

/* Responsive */
@media (max-width: 900px) {
  .product-detail { grid-template-columns: 1fr; }
  .pd-img-wrap { max-width: 400px; }
}
</style>
