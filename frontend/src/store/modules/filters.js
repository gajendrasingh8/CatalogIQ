const state = () => ({
  facets: {
    categories:   [],
    brands:       [],
    price_ranges: [],
    ratings:      [],
  },
  active: {
    category_id: [],
    brand_id:    [],
    price_min:   null,
    price_max:   null,
    rating_min:  null,
  },
})

const mutations = {
  SET_FACETS(state, facets) {
    state.facets = { ...state.facets, ...facets }
  },

  TOGGLE_CATEGORY(state, id) {
    const idx = state.active.category_id.indexOf(id)
    if (idx === -1) state.active.category_id.push(id)
    else            state.active.category_id.splice(idx, 1)
  },

  TOGGLE_BRAND(state, id) {
    const idx = state.active.brand_id.indexOf(id)
    if (idx === -1) state.active.brand_id.push(id)
    else            state.active.brand_id.splice(idx, 1)
  },

  SET_PRICE_RANGE(state, { min, max }) {
    state.active.price_min = min ?? null
    state.active.price_max = max ?? null
  },

  SET_RATING_MIN(state, val) {
    state.active.rating_min = val
  },

  CLEAR_ALL_FILTERS(state) {
    state.active = {
      category_id: [],
      brand_id:    [],
      price_min:   null,
      price_max:   null,
      rating_min:  null,
    }
  },
}

const actions = {
  async toggleCategory({ commit, dispatch }, id) {
    commit('TOGGLE_CATEGORY', id)
    commit('search/SET_PAGE', 1, { root: true })
    await dispatch('search/fetchResults', {}, { root: true })
  },

  async toggleBrand({ commit, dispatch }, id) {
    commit('TOGGLE_BRAND', id)
    commit('search/SET_PAGE', 1, { root: true })
    await dispatch('search/fetchResults', {}, { root: true })
  },

  async setPriceRange({ commit, dispatch }, range) {
    commit('SET_PRICE_RANGE', range)
    commit('search/SET_PAGE', 1, { root: true })
    await dispatch('search/fetchResults', {}, { root: true })
  },

  async setRatingMin({ commit, dispatch }, val) {
    commit('SET_RATING_MIN', val)
    commit('search/SET_PAGE', 1, { root: true })
    await dispatch('search/fetchResults', {}, { root: true })
  },

  async clearAllFilters({ commit, dispatch }) {
    commit('CLEAR_ALL_FILTERS')
    commit('search/SET_PAGE', 1, { root: true })
    await dispatch('search/fetchResults', {}, { root: true })
  },
}

const getters = {
  activeFilterCount(state) {
    return (
      state.active.category_id.length +
      state.active.brand_id.length +
      (state.active.price_min != null || state.active.price_max != null ? 1 : 0) +
      (state.active.rating_min != null ? 1 : 0)
    )
  },
  hasActiveFilters: (state, getters) => getters.activeFilterCount > 0,
}

export default { namespaced: true, state, mutations, actions, getters }
