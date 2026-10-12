<template>
  <div class="loader-wrap" v-if="show">
    <div class="spinner"></div>
    <span v-if="label" class="loader-label">{{ label }}</span>
  </div>
</template>

<script setup>
defineProps({
  show:  { type: Boolean, default: true },
  label: { type: String, default: '' },
})
</script>

<style scoped>
.loader-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: var(--space-3);
  padding: var(--space-12);
}

.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

.loader-label {
  font-size: 14px;
  color: var(--color-text-muted);
}
</style>
