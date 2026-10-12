<template>
  <div class="filter-section" :class="{ collapsed }">
    <button class="section-toggle" @click="collapsed = !collapsed">
      <span class="section-title">{{ title }}</span>
      <span v-if="count" class="section-count">{{ count }}</span>
      <svg class="toggle-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <polyline points="6 9 12 15 18 9"/>
      </svg>
    </button>
    <div v-show="!collapsed" class="section-body">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
defineProps({ title: String, count: Number })
const collapsed = ref(false)
</script>

<style scoped>
.filter-section {
  border-bottom: 1px solid var(--color-border);
  padding: var(--space-3) 0;
}

.section-toggle {
  width: 100%;
  display: flex;
  align-items: center;
  gap: var(--space-2);
  color: var(--color-text);
  font-size: 13px;
  font-weight: 600;
  padding: var(--space-1) var(--space-2);
  border-radius: var(--radius-sm);
  transition: background var(--transition-fast);
}

.section-toggle:hover {
  background: var(--color-surface-3);
}

.section-title {
  flex: 1;
  text-align: left;
  letter-spacing: 0.02em;
}

.section-count {
  font-size: 11px;
  color: var(--color-text-faint);
  font-family: var(--font-mono);
}

.toggle-icon {
  transition: transform var(--transition-fast);
  color: var(--color-text-muted);
}

.collapsed .toggle-icon {
  transform: rotate(-90deg);
}

.section-body {
  margin-top: var(--space-2);
  display: flex;
  flex-direction: column;
  gap: 2px;
}
</style>
