export class CookieEraser {
    private datastore: any;
    private domain: string;
    private cookies: any;

    constructor(datastore: any, cookies: any) {
        this.datastore = datastore;
        this.cookies = cookies;
        this.domain = this.getDomain();
    }

    execute(): void {
        const _cookies = this.getCookies();

        //matching the values saved in the cookie(when exist)
        //one step before was verified if the cookie was set and which values were set
        let config = this.datastore.cookiesTemp;
        let itemValues = this.datastore.cookieValues;
        //it is going to storage all regex values that come from each item(configuration that ws set in the admin area)
        //and used to delete the cookies that match this values
        let allRegexValues: any[] = [];

        //iteration over the object
        //config contains the configurations combined from
        //the db and the configurations that were set for the user
        for (let index in config) {

            //read if there are in the config setting(checkbox => false)
            //in this case erase the cookie if was set

            if (!config[index].checked) {
                //get the regex from the item: it is an string and needs
                //to be converted in an array to iterate over.
                //check first if exist the config
                if (itemValues[index].values) {
                    let values = itemValues[index].values;
                    //if values(array) in not empty that mean that contains regex values
                    if (values.length > 0) {
                        //for each value must be converted in to
                        // an array(in case that are space values in the same string)
                        values.forEach((value: any) => {
                            //double check if exist regex
                            if(typeof value === 'object' && (value.hasOwnProperty('regex') && value.regex)){
                                allRegexValues.push(value.regex.split(' '))
                            }
                        });
                    }

                }
                //check if the extra regex was filled in
                if(config[index].extraRegex){
                    allRegexValues.push(config[index].extraRegex.split(' '));
                }
            }
        }
        
        allRegexValues = allRegexValues.flat();

        //if there is something to delete - erase
        if (allRegexValues.length > 0) {
            allRegexValues.forEach((cName: string) => {
                for (let cn in _cookies) {
                    if (cn.match(cName)) {
                        document.cookie = cn + "= ; expires = Thu, 01 Jan 1970 00:00:00 GM";
                        try {
                            this.cookies.remove(cn);
                            this.cookies.remove(cn, '');
                            this.cookies.remove(cn, '', this.domain);
                            this.cookies.remove(cn, '/');
                            this.cookies.remove(cn, '/', this.domain);
                        } catch (e) {
                        }
                    }
                }
            });
        }
    }
    getCookies = function () {
        var cookies = {};
        if (document.cookie && document.cookie != '') {
            var split = document.cookie.split(';');
            for (var i = 0; i < split.length; i++) {
                var name_value = split[i].split("=");
                name_value[0] = name_value[0].replace(/^ /, '');
                // @ts-ignore
                cookies[decodeURIComponent(name_value[0])] = decodeURIComponent(name_value[1]);
            }
        }
        return cookies;
    }

    getDomain(): string {
        const url = document.createElement('a');
        url.setAttribute('href', window.location.href);
        return url.hostname;
    }

}