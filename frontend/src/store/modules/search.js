import api from '../../services/api'

const state = () => ({
  query:        '',
  results:      [],
  total:        0,
  page:         1,
  limit:        20,
  sort:         'relevance',
  engine:       'both',
  resultEngine: null,
  loading:      false,
  error:        null,
  timing: {
    mysql_ms:     null,
    mysql_method: null,
    es_ms:        null,
    es_available: null,
    es_requested: true,
  },
  autocomplete: {
    products:   [],
    categories: [],
    loading:    false,
  },
})

const mutations = {
  SET_QUERY(state, q)            { state.query = q },
  SET_RESULTS(state, results)    { state.results = results },
  SET_TOTAL(state, total)        { state.total = total },
  SET_PAGE(state, page)          { state.page = page },
  SET_LIMIT(state, limit)        { state.limit = limit },
  SET_SORT(state, sort)          { state.sort = sort },
  SET_ENGINE(state, engine)      { state.engine = engine },
  SET_RESULT_ENGINE(state, engine) { state.resultEngine = engine },
  SET_LOADING(state, loading)    { state.loading = loading },
  SET_ERROR(state, error)        { state.error = error },
  SET_TIMING(state, timing)      { state.timing = { ...state.timing, ...timing } },
  SET_AUTOCOMPLETE(state, data)  { state.autocomplete = { ...state.autocomplete, ...data } },
  CLEAR_AUTOCOMPLETE(state)      { state.autocomplete = { products: [], categories: [], loading: false } },
}

const actions = {
  async fetchResults({ commit, state, rootState }, opts = {}) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)

    const filters  = rootState.filters.active
    const page     = opts.page  ?? state.page
    const sort     = opts.sort  ?? state.sort
    const query    = opts.query ?? state.query
    const engine   = opts.engine ?? state.engine

    if (opts.page  !== undefined) commit('SET_PAGE', opts.page)
    if (opts.sort  !== undefined) commit('SET_SORT', opts.sort)
    if (opts.query !== undefined) commit('SET_QUERY', opts.query)
    if (opts.engine !== undefined) commit('SET_ENGINE', engine)

    try {
      const params = {
        q:         query,
        page,
        limit:     state.limit,
        sort,
        engine,
        ...buildFilterParams(filters),
      }

      const res = await api.searchProducts(params)
      const d   = res.data

      commit('SET_RESULTS',  d.data  ?? [])
      commit('SET_TOTAL',    d.meta?.total ?? 0)
      commit('SET_RESULT_ENGINE', d.meta?.engine ?? null)
      commit('SET_TIMING',   d.timing ?? {})
      commit('filters/SET_FACETS', d.facets ?? {}, { root: true })
    } catch (err) {
      commit('SET_ERROR', err.message)
      commit('SET_RESULTS', [])
      commit('SET_TOTAL', 0)
      commit('SET_RESULT_ENGINE', null)
      commit('SET_TIMING', {
        mysql_ms: null,
        mysql_method: null,
        es_ms: null,
        es_available: null,
        es_requested: state.engine !== 'mysql',
      })
    } finally {
      commit('SET_LOADING', false)
    }
  },

  async fetchAutocomplete({ commit, state }, query) {
    if (query.length < 2) {
      commit('CLEAR_AUTOCOMPLETE')
      return
    }
    commit('SET_AUTOCOMPLETE', { loading: true })
    try {
      const res = await api.getAutocomplete(query, state.engine)
      commit('SET_AUTOCOMPLETE', {
        products:   res.data.products   ?? [],
        categories: res.data.categories ?? [],
        loading:    false,
      })
    } catch {
      commit('SET_AUTOCOMPLETE', { loading: false })
    }
  },
}

const getters = {
  totalPages:  state => state.limit > 0 ? Math.ceil(state.total / state.limit) : 1,
  hasResults:  state => state.results.length > 0,
  isLoading:   state => state.loading,
  hasQuery:    state => state.query.trim().length > 0,
}

function buildFilterParams(filters) {
  const p = {}
  if (filters.category_id?.length) p.category_id = filters.category_id
  if (filters.brand_id?.length)    p.brand_id     = filters.brand_id
  if (filters.price_min != null)   p.price_min    = filters.price_min
  if (filters.price_max != null)   p.price_max    = filters.price_max
  if (filters.rating_min != null)  p.rating_min   = filters.rating_min
  return p
}

export default { namespaced: true, state, mutations, actions, getters }
