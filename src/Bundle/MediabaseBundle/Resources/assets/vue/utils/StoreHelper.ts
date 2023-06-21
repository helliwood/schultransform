export class StoreHelper {

    public sanitize(word: any) {
        if (StoreHelper.hasSpecialCharsGral(word) || StoreHelper.hasSpecialCharsGer(word)) {
            return encodeURIComponent(word);
        }
        return word;
    }

    private static hasSpecialCharsGral(word: any) {
        let patter = /[^a-zA-Z|ä|Ä|ö|Ö|ü|Ü|ß]/g;
        return word.match(patter);
    }

    private static hasSpecialCharsGer(word: any) {
        let patter = /[ä|Ä|ö|Ö|ü|Ü|ß]/g;
        return word.match(patter);
    }

}