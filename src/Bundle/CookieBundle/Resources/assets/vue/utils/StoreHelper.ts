import Vue from "vue";
import store from "../store/coostore";

export class StoreHelper {

    protected store: any;

    public constructor(state: any) {
        this.store = state;
    }
    //get the data for save in the cookie 'cookieControlPrefs'
    public getDataForSaveInCookie() {
        //loop over the data that was set
        let data = this.store.cookiesTemp;
        let objToReturn = {}
        for (let index in data) {
            let id = data[index].id;
            let checked = data[index].checked;
            let name = data[index].name;
            // @ts-ignore
            objToReturn[id] = {
                id: id,
                name: name,
                checked: checked,
            }
        }
        return objToReturn;
    }
}