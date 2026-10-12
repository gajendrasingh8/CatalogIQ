import api from '../../services/api'

const state = () => ({
  current: null,
  loading: false,
  error:   null,
})

const mutations = {
  SET_PRODUCT(state, product) { state.current = product },
  SET_LOADING(state, val)     { state.loading = val },
  SET_ERROR(state, err)       { state.error = err },
}

const actions = {
  async fetchProduct({ commit }, slug) {
    commit('SET_LOADING', true)
    commit('SET_ERROR', null)
    commit('SET_PRODUCT', null)
    try {
      const res = await api.getProduct(slug)
      commit('SET_PRODUCT', res.data?.data ?? res.data)
    } catch (err) {
      commit('SET_ERROR', err.message)
    } finally {
      commit('SET_LOADING', false)
    }
  },
}

export default { namespaced: true, state, mutations, actions }
