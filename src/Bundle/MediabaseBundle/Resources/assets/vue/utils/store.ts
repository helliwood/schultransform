import Vue from "vue";
import axios from "axios";
import {StoreHelper} from "./StoreHelper";

export default {
    namespaced: true,
    state: {
        data: {
            empty: true,
        },
        parse: null,
        optParameters: null,
        startFolder: null,
        searchResults: {},
        searchMinChars: 1,
        url: '/Backend/MediaBase',
        urlSearch: '/Backend/MediaBase/search/',
        loading: false,
        currentItem: null,
        uploadButton: false,
        searchActive: false,
        searchWord: '',
        renderMode: null,
        bus: Vue,
        //flag to know if the user switch the views and
        viewWasChanged: false,

    },
    mutations: {
        setIsLoading(state: any, data: boolean) {
            state.loading = data;
        },
        setData(state: any, data: any) {
            state.data = data;
        },
        setUrl(state: any, url: any) {
            state.url = url;
        },
        //to identify which is the current element
        setCurrentItem(state: any, data: any) {
            state.currentItem = data;
        },
        setCurrentData(state: any, data: any) {
            state.data = data
        },
        setSearchActive(state: any, data: boolean) {
            //emit event
            if (!data) {
                state.bus.$emit('clear-search')
            }

            state.searchActive = data;
        },
        setSearchWord(state: any, data: any) {
            state.searchWord = data;
        },
        setRenderMode(state: any, data: any) {
            state.renderMode = data;
        },
        setSearchResults(state: any, data: any) {
            state.searchResults = data;
        },
        setBus(state: any, data: any) {
            state.bus = data;
        },
        setParse(state: any, data: any) {
            state.parse = data;
        },
        setStartFolder(state: any, data: any) {
            state.startFolder = data;
        },
        setViewWasChanged(state: any) {
            state.viewWasChanged = true;
        },
    },
    getters: {
        getUploadButton: function (state: any) {
            state.uploadbutton = !!state.currentItem;
            return state.uploadbutton;
        },
        getData(state: any) {
            if (state.searchActive) {
                return state.searchResults;
            } else {
                return state.data;
            }
        },
        getUrl(state: any) {
            return state.url;
        },
        getUrlSearch(state: any) {
            return state.urlSearch;
        },
        isLoading(state: any) {
            return state.loading;
        },
        getCurrentItem(state: any) {
            return state.currentItem;
        },
        getCurrentFolder(state: any) {
            let toReturn = '';
            if (!state.loading) {
                if (state.data.tree && (state.data.tree.length && (state.data.tree.length > 0))) {
                    toReturn = state.data.tree[state.data.tree.length - 1].name;
                }

                return toReturn !== '' ? toReturn : 'Root';

            }


        },
        getSearchActive(state: any) {
            return state.searchActive;
        },
        getSearchWord(state: any) {
            return state.searchWord;
        },
        getRenderMode(state: any) {
            return state.renderMode;
        },
        getSearchResults(state: any) {
            return state.searchResults;
        },
        getSearchMinChars(state: any) {
            return state.searchMinChars;
        },
        getBus(state: any) {
            return state.bus;
        },
        getParse(state: any) {
            return state.parse;
        },
        getStartFolder(state: any) {
            return state.startFolder;
        },
        getViewWasChanged(state: any) {
            return state.viewWasChanged;
        },
        getOptParameters(state: any) {
            return state.optParameters;
        },
    },
    actions: {
        loadDataWithUrl({commit, state}: any, url: string) {
            commit("setIsLoading", true);

            //if renderMode 'icons' send a flag to the end point
            if (state.renderMode === 'icons') {
                url += '?page=1&size=40&sort=order&sortDesc=false';
                //set the parameters if they exist
                if (state.optParameters) {
                    url += '&' + state.optParameters;
                }
            }

            axios.get(url).then(response => {
                commit("setData", response.data);
                commit("setUrl", url);
                commit("setIsLoading", false);
            }).catch(e => {
                commit("setIsLoading", true);
            });
        },
        dispatchCurrentItem({commit}: any, currentItem: any) {
            commit("setCurrentItem", currentItem);
        },
        dispatchUrl({commit}: any, url: any) {
            commit("setUrl", url);
        },
        dispatchCurrentData({commit}: any, data: any) {
            commit("setCurrentData", data);
        },
        dispatchSearchActive({commit}: any, data: boolean) {
            commit("setSearchActive", data);
        },
        dispatchSearchWord({commit}: any, data: any) {
            commit("setSearchWord", data);
        },
        dispatchRenderMode({commit}: any, data: any) {
            commit("setRenderMode", data);
        },
        dispatchSearchResults({commit}: any, data: any) {
            commit("setSearchResults", data);
        },

        dispatchBus({commit}: any, data: any) {
            commit("setBus", data);
        },
        dispatchParse({commit}: any, data: any) {
            commit("setParse", data);
        },
        dispatchStartFolder({commit}: any, data: any) {
            commit("setStartFolder", data);
        },
        dispatchViewWasChanged({commit}: any) {
            commit("setViewWasChanged");
        },
        dispatchOptParameters({commit, state}: any, data: any) {
            state.optParameters = data;
        },
        resetUrl({commit, state}: any) {
            state.url = '/Backend/MediaBase';
        },
        resetData({commit, state}: any) {
            state.data = {
                empty: true,
            };
        },

        //this function perform the media search.
        searchMedia({commit, state}: any) {
            let helper = new StoreHelper();
            let wordToSearch = helper.sanitize(state.searchWord);
            let searchUrl = state.urlSearch;
            let url = searchUrl + wordToSearch;
            if (state.optParameters) {
                url += '?' + state.optParameters;
            }
            axios.get(url).then(result => {
                state.searchResults = result.data;
            }).catch(e => {
            });
        },
    },
}