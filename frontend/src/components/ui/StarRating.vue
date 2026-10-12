<template>
  <div class="star-rating">
    <span
      v-for="s in 5"
      :key="s"
      class="star"
      :class="{
        full:  s <= Math.floor(rating),
        half:  s === Math.ceil(rating) && rating % 1 >= 0.5,
        empty: s > Math.ceil(rating)
      }"
    >★</span>
    <span v-if="count !== undefined" class="review-count">({{ count?.toLocaleString() }})</span>
    <span class="rating-num">{{ rating?.toFixed(1) }}</span>
  </div>
</template>

<script setup>
defineProps({
  rating: { type: Number, default: 0 },
  count:  { type: Number, default: undefined },
})
</script>

<style scoped>
.star-rating {
  display: flex;
  align-items: center;
  gap: 2px;
}

.star {
  font-size: 13px;
  color: var(--color-text-faint);
  transition: color var(--transition-fast);
}

.star.full { color: var(--color-warning); }
.star.half { color: var(--color-warning); opacity: 0.6; }

.review-count {
  font-size: 11px;
  color: var(--color-text-faint);
  margin-left: 3px;
}

.rating-num {
  font-size: 11px;
  font-weight: 600;
  color: var(--color-warning);
  margin-left: 2px;
  font-family: var(--font-mono);
}
</style>
