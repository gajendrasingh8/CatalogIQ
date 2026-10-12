import axios from 'axios'

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Request interceptor — log in dev
api.interceptors.request.use(config => config, error => Promise.reject(error))

// Response interceptor — normalize errors
api.interceptors.response.use(
  response => response,
  error => {
    const msg = error?.response?.data?.detail || error?.message || 'Network error'
    return Promise.reject(new Error(msg))
  }
)

export default {
  /**
   * Main search endpoint — returns results, facets, pagination, timing.
   */
  searchProducts(params = {}) {
    return api.get('/search', { params })
  },

  /**
   * Typeahead autocomplete — returns product names + categories.
   */
  getAutocomplete(q, engine = 'both') {
    return api.get('/autocomplete', { params: { q, engine } })
  },

  /**
   * All products (browse mode, no search query).
   */
  getProducts(params = {}) {
    return api.get('/products', { params })
  },

  /**
   * Single product detail by slug.
   */
  getProduct(slug) {
    return api.get(`/products/${slug}`)
  },

  /**
   * All categories with product count.
   */
  getCategories() {
    return api.get('/categories')
  },

  /**
   * All brands with product count.
   */
  getBrands() {
    return api.get('/brands')
  },

  /**
   * Elasticsearch health status.
   */
  getElasticStatus() {
    return api.get('/elastic/status')
  },
}
