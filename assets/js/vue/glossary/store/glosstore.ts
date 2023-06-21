import Vue from "vue";
import axios from "axios";


export default {
    namespaced: true,
    state: {
        data: {}
    },
    mutations: {
        setData(state: any, data: any) {
            state.data = data;
        },
        setClearData(state: any) {
            state.data = {};
        },
    },
    getters: {
        getData(state: any) {
            return state.data;
        },
        getShortDescription(state: any) {
            if(state.data.data){
                return state.data.data.shortDescription;
            }
        },
        getRelatedWords(state: any) {
            if(state.data.data){
                return state.data.data.relatedWords;
            }
        },
    },
    actions: {
        dispatchData({commit}: any, data: any) {
            commit("setData", data);
        },
        dispatchClearData({commit}: any) {
            commit("setClearData");
        },

    }
}