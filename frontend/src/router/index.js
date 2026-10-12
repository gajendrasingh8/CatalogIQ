import { createRouter, createWebHistory } from 'vue-router'
import SearchView from '../views/SearchView.vue'
import ProductView from '../views/ProductView.vue'

const routes = [
  {
    path: '/',
    name: 'search',
    component: SearchView,
    meta: { title: 'CatalogIQ — Product Search' },
  },
  {
    path: '/product/:slug',
    name: 'product',
    component: ProductView,
    meta: { title: 'Product Detail — CatalogIQ' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) return savedPosition
    return { top: 0, behavior: 'smooth' }
  },
})

// Update document title per route
router.afterEach(to => {
  document.title = to.meta?.title || 'CatalogIQ'
})

export default router
