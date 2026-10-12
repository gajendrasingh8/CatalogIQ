<template>
  <header class="app-header">
    <div class="header-inner">
      <!-- Logo -->
      <router-link to="/" class="logo" id="logo-link">
        <div class="logo-icon">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
            <path d="M12 2L2 7l10 5 10-5-10-5z" fill="url(#g1)" />
            <path d="M2 17l10 5 10-5" stroke="url(#g2)" stroke-width="1.5" stroke-linecap="round" />
            <path d="M2 12l10 5 10-5" stroke="url(#g2)" stroke-width="1.5" stroke-linecap="round" />
            <defs>
              <linearGradient id="g1" x1="2" y1="2" x2="22" y2="12" gradientUnits="userSpaceOnUse">
                <stop stop-color="#5546e8" /><stop offset="1" stop-color="#0a9e80" />
              </linearGradient>
              <linearGradient id="g2" x1="2" y1="2" x2="22" y2="22" gradientUnits="userSpaceOnUse">
                <stop stop-color="#5546e8" /><stop offset="1" stop-color="#0a9e80" />
              </linearGradient>
            </defs>
          </svg>
        </div>
        <span class="logo-text">Catalog<span class="logo-iq">IQ</span></span>
      </router-link>

      <!-- ES Status Pill -->
      <div class="es-status" :class="esAvailable ? 'es-status--on' : 'es-status--off'" :title="esAvailable ? 'Elasticsearch online' : 'Elasticsearch offline — using MySQL'">
        <span class="es-dot"></span>
        <span>{{ esAvailable ? 'ES Online' : 'ES Offline' }}</span>
      </div>

      <!-- Right -->
      <div class="header-right">
        <span class="header-tag">MySQL · Elasticsearch · Vue 3</span>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '../../services/api'

const esAvailable = ref(false)

onMounted(async () => {
  try {
    const res = await api.getElasticStatus()
    esAvailable.value = res.data?.elasticsearch?.available ?? false
  } catch {
    esAvailable.value = false
  }
})
</script>

<style scoped>
.app-header {
  position: sticky;
  top: 0;
  z-index: 100;
  height: var(--header-height);
  background: rgba(255, 255, 255, 0.88);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--color-border);
  box-shadow: 0 1px 0 rgba(0,0,0,0.06);
}

.header-inner {
  max-width: var(--max-content);
  margin: 0 auto;
  height: 100%;
  padding: 0 var(--space-6);
  display: flex;
  align-items: center;
  gap: var(--space-4);
}

.logo {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  flex-shrink: 0;
}

.logo-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: rgba(85, 70, 232, 0.08);
  border: 1px solid rgba(85, 70, 232, 0.2);
  border-radius: var(--radius-md);
}

.logo-text {
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -0.02em;
  color: var(--color-text);
}

.logo-iq {
  background: linear-gradient(135deg, #5546e8, #0a9e80);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.es-status {
  display: flex;
  align-items: center;
  gap: var(--space-1);
  padding: 4px 10px;
  border-radius: var(--radius-full);
  font-size: 12px;
  font-weight: 500;
  cursor: default;
  transition: all var(--transition-fast);
}

.es-status--on {
  background: rgba(5, 150, 105, 0.08);
  border: 1px solid rgba(5, 150, 105, 0.22);
  color: #059669;
}

.es-status--off {
  background: rgba(217, 119, 6, 0.08);
  border: 1px solid rgba(217, 119, 6, 0.22);
  color: #d97706;
}

.es-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
  position: relative;
}

.es-status--on .es-dot::after {
  content: '';
  position: absolute;
  inset: -3px;
  border-radius: 50%;
  background: currentColor;
  animation: pulse-ring 1.5s ease-out infinite;
}

.header-right { margin-left: auto; }

.header-tag {
  font-size: 12px;
  color: var(--color-text-muted);
  font-family: var(--font-mono);
  letter-spacing: 0.02em;
}
</style>
