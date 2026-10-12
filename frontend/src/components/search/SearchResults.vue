<template>
  <!-- Skeleton grid while loading -->
  <div v-if="loading && !results.length" class="results-grid">
    <div v-for="n in 12" :key="n" class="product-skeleton">
      <div class="skeleton skeleton-img"></div>
      <div class="skeleton-body">
        <div class="skeleton skeleton-line short"></div>
        <div class="skeleton skeleton-line"></div>
        <div class="skeleton skeleton-line medium"></div>
        <div class="skeleton skeleton-price"></div>
      </div>
    </div>
  </div>

  <!-- Empty state -->
  <div v-else-if="!loading && !results.length" class="empty-state">
    <div class="empty-icon">🔍</div>
    <h3 class="empty-title">No products found</h3>
    <p class="empty-sub">Try adjusting your search query or remove some filters</p>
  </div>

  <!-- Results grid -->
  <div v-else class="results-grid">
    <ProductCard
      v-for="product in results"
      :key="product.id ?? product.slug"
      :product="product"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useStore } from 'vuex'
import ProductCard from '../product/ProductCard.vue'

const store   = useStore()
const results = computed(() => store.state.search.results)
const loading = computed(() => store.state.search.loading)
</script>

<style scoped>
.results-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: var(--space-4);
  padding: var(--space-4) 0;
}

/* Skeleton */
.product-skeleton {
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.skeleton-img { height: 180px; border-radius: 0; }

.skeleton-body {
  padding: var(--space-3);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.skeleton-line { height: 12px; }
.skeleton-line.short { width: 50%; height: 10px; }
.skeleton-line.medium { width: 75%; }
.skeleton-price { height: 20px; width: 60%; margin-top: var(--space-1); }

/* Empty state */
.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: var(--space-16) var(--space-8);
}

.empty-icon { font-size: 56px; margin-bottom: var(--space-4); opacity: 0.4; }

.empty-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: var(--space-2);
}

.empty-sub {
  font-size: 15px;
  color: var(--color-text-muted);
}
</style>
