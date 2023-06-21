import {CookieEraser} from "../utils/CookieEraser";
import {StoreHelper} from "../utils/StoreHelper";

export default {
    namespaced: true,
    state: {
        //data coming from the database
        data: {},
        //temp object: will be overrides and used to save the data in the cookie 'cookieControlPrefs'
        cookiesTemp: {},
        //prop to store only the values(regex)
        cookieValues: {},
        //variable to storage the key value that must highlighted
        classHighlighted: null,
    },
    mutations: {
        setData(state: any, data: any) {
            state.data = data;
            //create the object to interchange the values of the cookies
            //this object is going to be changed everytime that
            //the user check or uncheck the box
            let items = state.data.item;

            for (let it in items) {
                //check if the extra regex field was filled in
                let extraRegex = null;
                if (items[it].regex) {
                    extraRegex = items[it].regex;
                }

                let necessary = false;
                if (items[it].necessary) {
                    necessary = true;
                }
                // @ts-ignore
                state.cookiesTemp[items[it].id] = {
                    id: items[it].id,
                    name: items[it].name,
                    checked: necessary,
                    necessary: necessary,
                    values: items[it].variation,
                    extraRegex: extraRegex,
                }

                //ask if there ara variations(regex values)
                let variations = null;

                if (typeof items[it].variations === 'object'
                    && (items[it].variations.length > 0)
                ) {
                    variations = items[it].variations;
                    //check for the classHighlighted (for example: 'Youtube')
                    if (state.classHighlighted) {
                        for (let i in variations) {
                            if (variations[i].key == state.classHighlighted) {
                                state.data.item[it].highlight = true;
                                break;
                            }
                        }
                    }

                }

                // @ts-ignore
                state.cookieValues[items[it].id] = {
                    id: items[it].id,
                    values: variations,
                }
            }
        },
        setUpdateCookie(state: any, data: any) {
            //double check for the necessary cookies
            if (state.cookiesTemp[data.id].necessary) {
                return false;
            }
            state.cookiesTemp[data.id].checked = data.checked;
        },
        setCookieValues(state: any, data: any) {
            //when a cookie was saved retrieve the content and set to the
            //property
            //verify if there are items(coming from the ajax request)
            if (state.data.item) {
                for (let it in state.cookiesTemp) {
                    //double check for the necessary cookies
                    if (!state.cookiesTemp[it].necessary) {
                        if (data[it]) {
                            state.cookiesTemp[it].checked = data[it].checked;
                        }
                    }
                }
            }
        },
        setAllAccept(state: any) {
            for (let it in state.cookiesTemp) {
                state.cookiesTemp[it].checked = true;
            }
        },
        setClassHighlighted(state: any, data: any) {
            state.classHighlighted = data;
        },
    },
    getters: {
        getData(state: any) {
            return state.data;
        },

        getTempCookies(state: any) {
            return state.cookiesTemp;
        },

        //preparing the data for storage in the cookie
        getItemsForSetCookie(state: any) {
            return state.cookiesTemp;
        },
        //preparing the data for storage in the cookie
        getItemsForSetCookieAllAccept(state: any) {
            return state.cookiesTemp;
        },

    },
    actions: {
        dispatchData({commit}: any, data: any) {
            commit("setData", data);
        },
        dispatchClearData({commit}: any) {
            commit("setClearData");
        },

        //set the values of the cookie when the user check/uncheck the input
        dispatchUpdateCookie({commit}: any, data: any) {
            commit("setUpdateCookie", data);
        },

        //set the values of the cookies(false/true) coming from the saved cookie
        dispatchCookieValues({commit}: any, data: any) {
            commit("setCookieValues", data);
        },
        //set the values of the cookies(false/true) coming from the saved cookie
        dispatchSetAllAccept({commit}: any) {
            commit("setAllAccept");
        },
        //execute the comparison between the saved preferences from the  user(cookieControlPrefs)
        //and the data
        dispatchExecuteEraserTasks({commit, state}: any, cookieVue: any) {
            //get the eraser
            let eraser = new CookieEraser(state, cookieVue);
            eraser.execute()
        },
        //set the cookie
        dispatchSetCookies({commit, state}: any, cookie: any) {
            //get the data to save in the cookie
            let helper = new StoreHelper(state);
            let dataForCookie = helper.getDataForSaveInCookie();

            //set the cookieControlPrefs
            let cookieDuration = state.data.cookieDuration ? state.data.cookieDuration : 30;
            cookie.set('cookieControlPrefs', dataForCookie, cookieDuration + 'd', '/');
            let eraser = new CookieEraser(state, cookie);
            eraser.execute()
        },
        //all selected
        dispatchSetCookiesAllSelected({commit, state}: any, cookie: any) {
            //get the data to save in the cookie
            let helper = new StoreHelper(state);
            let dataForCookie = helper.getDataForSaveInCookie();

            //set the cookieControlPrefs
            let cookieDuration = state.data.cookieDuration ? state.data.cookieDuration : 30;
            cookie.set('cookieControlPrefs', dataForCookie, cookieDuration + 'd', '/');
        },
        dispatchSetClassHighlighted({commit, state}: any, data: any) {
            commit("setClassHighlighted", data);
        }
    }
}