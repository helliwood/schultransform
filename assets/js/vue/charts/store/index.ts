// @ts-ignore
import Vue from 'vue';
// @ts-ignore
import Vuex from 'vuex';
import PieLevelApi from "./../utility/PieLevelApi";

Vue.use(Vuex);

export default new Vuex.Store({
  state: {
    displayedDetails: null,
    currentLevel: 0,
    visibleLevel: 100 as number,
    _selectedPie: [ null, null, null, null, null, null, null],
    optionsDetails: {
      renderer: "canvas",
      width: 'auto',
      height: "500",
    }
  },
  getters: {
    selectedPie: async state => {
      if(state._selectedPie[0] == null || state._selectedPie[0] == undefined) {
        var root = await PieLevelApi.getRootPie();
        // @ts-ignore
        state._selectedPie = [ root, null, null, null, null, null, null];
      }
      return state._selectedPie;
    }
  },
  mutations: {
  },
  actions: {
  },
  modules: {
  }
})
