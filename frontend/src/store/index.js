import { createStore } from 'vuex'
import search   from './modules/search'
import filters  from './modules/filters'
import products from './modules/products'

export default createStore({
  modules: { search, filters, products },
  strict: import.meta.env.DEV,
})
