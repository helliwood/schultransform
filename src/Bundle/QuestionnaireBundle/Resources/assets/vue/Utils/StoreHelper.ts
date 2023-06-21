import Vue from "vue";
import store from "../store/store";

export class StoreHelper {

    protected store: any;

    public constructor(state: any) {
        this.store = state;
    }

    //returns the array with the match list: -> the array(pointer) ['page_name', 'group_index', 'question_index']
    public getMatchList() {
        //creates the match table: array[pointer] -> [0] indexGroup, [1] indexQuestion
        let groups = this.store.data.questionGroups;
        let returnArray = [];
        let groupLength = groups.length;
        let counter = 0;
        for (let i = 0; i < groupLength; i++) {
            let questionsLength = groups[i].questions.length;
            for (let q = 0; q <= questionsLength; q++) {

                /*******************************
                 * Returns the match list(array) in order to navigate in to the questionnaire.
                 * 1) The first item of the array is the name of the page: 'start','group','inter_page'
                 * 'formal' and 'final', and some of them have child components(Question: multiple,
                 * single,yes no, long text..., Formal: title, question, Final(evaluation...))
                 *
                 * 2) The idea in order to animate the pagination(go next and back with the pages moving: with margin)
                 * is that every page is following for a inter page in order to set everytime a class for animate.
                 * this animation base on two classes: 'animate-questionnaire-back' and 'animate-questionnaire-next' to recreate
                 * the illusion of paginating over the questionnaire.
                 *
                 * 3) The second item of the array is the group id.
                 *
                 * 4) The third item of the array is the question number: only need it fot the question type
                 *
                 * 5) the main idea of this method is to have a kind of table with an identifiers(pointers) which everyone
                 * is going to point a exactly page, in oder words, the index of
                 * the array will serve to determinate precisely where the content is:
                 * matchList[pointer]['page name'][groupIndex][questionIndex]
                 * for example:
                 * matchList[0]['start'][0][0]
                 * matchList[0]['group'][0][0]
                 * matchList[0]['question'][0][0]
                 * ...
                 * matchList[0]['group'][1][0]
                 * matchList[0]['formal'][1]['number of the formal page: FormalQuestion component']
                 *
                 * 6) The pointer is sent over the application and will update its value in the store everytime is needed
                 * in order to give an example of the practical use:
                 *
                 * store.data -> contains all the information of the questionnaire
                 *
                 * a component call the updatePointer action on the store giving the pointer number:
                 * updatePointer, {flag: 'back', 'next' or 'nav', pointer:'when need it'}
                 *
                 * the updatePointer do following:
                 *
                 * state.pointerIndex = number; -> this call comes from a component, and gives a flag:('back','next', 'nav') and pointer: 6
                 * let indexArray = state.matchList[state.pointerIndex];
                 * in this case:  state.matchList[6] -> "pointer": 6, "value": [ "question", 0, 1 ]
                 *
                 * and then set the indexes for the group and the question in order to go to the
                 * content in the state.data.questionGroups:
                 *
                 * state.pointerData.groupIndex = indexArray[1]; -> the index of the group
                 * state.pointerData.questionIndex = indexArray[2]; -> the index of the question
                 *
                 * for example:
                 *
                 * state.data.questionGroups[state.pointerData.groupIndex].questions[state.pointerData.questionIndex]
                 *
                 * On the store:
                 * getDataToShow -> is call from the main component: Questionnaire
                 *
                 *******************************/

                if (questionsLength > q) {


                    let questionId = null;
                    if (this.store.data.questionGroups[i].questions[q]) {
                        let question = this.store.data.questionGroups[i].questions[q];

                        //check the number of chars of the opinion question
                        if (question.type && (question.type === 'opinion_scale')
                            && question.question) {
                            let numberOfGrow = StoreHelper.countChars(question.question);
                            if (numberOfGrow) {
                                this.store.data.questionGroups[i].questions[q].classCssMobil = 'q-grow-mobil-' + numberOfGrow;

                            }

                        }

                        if (question.hasOwnProperty('id')) {
                            //check if the object has the id prop.
                            questionId = this.store.data.questionGroups[i].questions[q].id;
                        }
                        if (question.properties &&
                            (question.properties.hasOwnProperty('allow_other_choice'))) {
                            //check if the question has the option 'allow_other_choice' true
                            //and count the number of choices
                            if (question.type === 'multiple_choice') {

                                if (typeof question.choices === 'object') {
                                    if (question.properties.allow_other_choice) {
                                        //case with  other choice
                                        if (question.choices.length > 4) {
                                            //set the prop class grow
                                            //count the total chars in the options
                                            let growSize = StoreHelper.countCharsInOptions(question.choices, 320, question.choices.length);
                                            // @ts-ignore
                                            if (growSize.desktop > 0) {
                                                // @ts-ignore
                                                this.store.data.questionGroups[i].questions[q].classCssDesktop = 'q-grow-desktop-' + growSize.desktop;
                                            }
                                            // @ts-ignore
                                            if (growSize.mobil > 0) {
                                                // @ts-ignore
                                                this.store.data.questionGroups[i].questions[q].classCssMobil = 'q-grow-mobil-' + growSize.mobil;
                                            }

                                        }
                                    } else {
                                        if (question.choices.length > 5) {
                                            //case without other choice
                                            //set the prop class grow
                                            let growSize = StoreHelper.countCharsInOptions(question.choices, 400, question.choices.length);
                                            // @ts-ignore
                                            if (growSize.desktop > 0) {
                                                // @ts-ignore
                                                this.store.data.questionGroups[i].questions[q].classCssDesktop = 'q-grow-desktop-' + growSize.desktop;
                                            }
                                            // @ts-ignore
                                            if (growSize.mobil > 0) {
                                                // @ts-ignore
                                                this.store.data.questionGroups[i].questions[q].classCssMobil = 'q-grow-mobil-' + growSize.mobil;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    returnArray[counter] = ['question', i, q, {'questionId': questionId}];
                    counter++
                    returnArray[counter] = ['inter_page', i, q];


                    //insert the pointer on the state.data.questions
                    //hard coded: 2 because the number of pages before the first
                    //question shows up
                    this.store.data.questionGroups[i].questions[q].pointer = counter + 2;

                    //TODO move all this code to a better place!!
                    //TODO find a bug: opinion_scale type -> it saves next question with an empty array!!
                    //*INSERT ALL QUESTIONS IN THE  'validateQuestions' ARRAY*//
                    //*WITH A FLAG: 'required' AND 'was_answered':  *//
                    let questionData = groups[i].questions[q];
                    //only if the question has the required property true
                    if (questionData.required || true) {
                        /** TODO IMPORTANT remove true **/
                            //only the first two groups set the required value to 'true'
                        let required = true;
                        if (i == 0 || i == 1) {
                            required = true;
                        } else {
                            required = false;
                        }

                        let questionValidation = {
                            'required': required,  //questionData.required, /**TODO IMPORTANT remove comment**/
                            'was_answered': false,
                            'questionId': questionData.id, //used to the validation process
                            'pointer': counter + 2,//one Start page and the first group page
                        };
                        Vue.set(this.store.validateQuestions, questionData.id, questionValidation);

                    }


                } else {
                    //end of the group array
                    if (groupLength > i + 1) {
                        returnArray[counter] = ['group', i + 1,];
                        counter++
                        returnArray[counter] = ['inter_page', i + 1];
                        ;

                    } else {
                        //add Formal page here
                        returnArray[counter] = ['formal', i, 1];
                        counter++
                        returnArray[counter] = ['inter_page', i];
                        counter++
                        returnArray[counter] = ['formal', i, 2, {'questionId': ''}];
                        counter++
                        returnArray[counter] = ['inter_page', i];
                        counter++
                        returnArray[counter] = ['final', i, {'questionId': ''}];
                    }
                }
                counter++;
            }
        }
        //add the Start(for the page) to the array or other values(pages wherever need it)

        returnArray.splice(0, 0, ['start', 0]);
        counter++;
        returnArray.splice(1, 0, ['inter_page', 0, 0])
        counter++;
        returnArray.splice(2, 0, ['group', 0, 0])
        counter++;
        returnArray.splice(3, 0, ['inter_page', 0, 0])
        this.store.matchList = returnArray;

        //set the category icon if available
        if (this.store.data.category && (this.store.data.category.icon)) {
            this.store.categoryIcon = this.store.data.category.icon;
        }
        //set the questionnaire icon if available
        if (this.store.data && (this.store.data.icon)) {
            this.store.questionnaireIcon = this.store.data.icon;
        }
        //set the if the user was filled out the questionnaire
        if (this.store.data && (this.store.data.user && (this.store.data.user.times) && (this.store.data.user.date))) {
            this.store.user = this.store.data.user;
        }


        return this.store;
    }

    public getMatchListBoard() {
        //creates the match table: array[pointer] -> [0] indexGroup, [1] indexQuestion
        let groups = this.store.data.questionGroups;
        let returnArray = [];
        let groupLength = groups.length;
        let counter = 0;
        for (let i = 0; i < groupLength; i++) {
            let questionsLength = groups[i].questions.length;
            for (let q = 0; q <= questionsLength; q++) {

                /*******************************
                 * Returns the match list(array) in order to navigate in to the questionnaire.
                 * 1) The first item of the array is the name of the page: 'start','group','inter_page'
                 * 'formal' and 'final', and some of them have child components(Question: multiple,
                 * single,yes no, long text..., Formal: title, question, Final(evaluation...))
                 *
                 * 2) The idea in order to animate the pagination(go next and back with the pages moving: with margin)
                 * is that every page is following for a inter page in order to set everytime a class for animate.
                 * this animation base on two classes: 'animate-questionnaire-back' and 'animate-questionnaire-next' to recreate
                 * the illusion of paginating over the questionnaire.
                 *
                 * 3) The second item of the array is the group id.
                 *
                 * 4) The third item of the array is the question number: only need it fot the question type
                 *
                 * 5) the main idea of this method is to have a kind of table with an identifiers(pointers) which everyone
                 * is going to point a exactly page, in oder words, the index of
                 * the array will serve to determinate precisely where the content is:
                 * matchList[pointer]['page name'][groupIndex][questionIndex]
                 * for example:
                 * matchList[0]['start'][0][0]
                 * matchList[0]['group'][0][0]
                 * matchList[0]['question'][0][0]
                 * ...
                 * matchList[0]['group'][1][0]
                 * matchList[0]['formal'][1]['number of the formal page: FormalQuestion component']
                 *
                 * 6) The pointer is sent over the application and will update its value in the store everytime is needed
                 * in order to give an example of the practical use:
                 *
                 * store.data -> contains all the information of the questionnaire
                 *
                 * a component call the updatePointer action on the store giving the pointer number:
                 * updatePointer, {flag: 'back', 'next' or 'nav', pointer:'when need it'}
                 *
                 * the updatePointer do following:
                 *
                 * state.pointerIndex = number; -> this call comes from a component, and gives a flag:('back','next', 'nav') and pointer: 6
                 * let indexArray = state.matchList[state.pointerIndex];
                 * in this case:  state.matchList[6] -> "pointer": 6, "value": [ "question", 0, 1 ]
                 *
                 * and then set the indexes for the group and the question in order to go to the
                 * content in the state.data.questionGroups:
                 *
                 * state.pointerData.groupIndex = indexArray[1]; -> the index of the group
                 * state.pointerData.questionIndex = indexArray[2]; -> the index of the question
                 *
                 * for example:
                 *
                 * state.data.questionGroups[state.pointerData.groupIndex].questions[state.pointerData.questionIndex]
                 *
                 * On the store:
                 * getDataToShow -> is call from the main component: Questionnaire
                 *
                 *******************************/

                if (questionsLength > q) {
                    let questionId = null;
                    if (this.store.data.questionGroups[i].questions[q]) {
                        let question = this.store.data.questionGroups[i].questions[q];

                        //check the number of chars of the opinion question
                        if (question.type && (question.type === 'opinion_scale')
                            && question.question) {
                            let numberOfGrow = StoreHelper.countChars(question.question);
                            if (numberOfGrow) {
                                this.store.data.questionGroups[i].questions[q].classCssMobil = 'q-grow-mobil-' + numberOfGrow;

                            }

                        }

                        if (question.hasOwnProperty('id')) {
                            //check if the object has the id prop.
                            questionId = this.store.data.questionGroups[i].questions[q].id;
                        }
                        if (question.properties &&
                            (question.properties.hasOwnProperty('allow_other_choice'))) {
                            //check if the question has the option 'allow_other_choice' true
                            //and count the number of choices
                            if (question.type === 'multiple_choice') {

                                if (typeof question.choices === 'object') {
                                    if (question.properties.allow_other_choice) {
                                        //case with  other choice
                                        if (question.choices.length > 4) {
                                            //set the prop class grow
                                            //count the total chars in the options
                                            let growSize = StoreHelper.countCharsInOptions(question.choices, 320, question.choices.length);
                                            // @ts-ignore
                                            if (growSize.desktop > 0) {
                                                // @ts-ignore
                                                this.store.data.questionGroups[i].questions[q].classCssDesktop = 'q-grow-desktop-' + growSize.desktop;
                                            }
                                            // @ts-ignore
                                            if (growSize.mobil > 0) {
                                                // @ts-ignore
                                                this.store.data.questionGroups[i].questions[q].classCssMobil = 'q-grow-mobil-' + growSize.mobil;
                                            }

                                        }
                                    } else {
                                        if (question.choices.length > 5) {
                                            //case without other choice
                                            //set the prop class grow
                                            let growSize = StoreHelper.countCharsInOptions(question.choices, 400, question.choices.length);
                                            // @ts-ignore
                                            if (growSize.desktop > 0) {
                                                // @ts-ignore
                                                this.store.data.questionGroups[i].questions[q].classCssDesktop = 'q-grow-desktop-' + growSize.desktop;
                                            }
                                            // @ts-ignore
                                            if (growSize.mobil > 0) {
                                                // @ts-ignore
                                                this.store.data.questionGroups[i].questions[q].classCssMobil = 'q-grow-mobil-' + growSize.mobil;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (groups[i].questions[q].question.match(/Sind Sie einverstanden/)
                    || groups[i].questions[q].type == 'rating'
                    ){
                        //change the type of the question
                        if(groups[i].questions[q].question.match(/Sind Sie einverstanden/)){
                            returnArray[counter] = ['formal', i, 2, {'questionId': ''}];
                            counter++
                            returnArray[counter] = ['inter_page', i, q];
                        }else{
                            if (groups[i].questions[q].type == 'rating'){
                                returnArray[counter] = ['final', i, {'questionId': ''}];
                            }
                        }

                    }else{
                        returnArray[counter] = ['question', i, q, {'questionId': questionId}];
                        counter++
                        returnArray[counter] = ['inter_page', i, q];
                    }


                    //insert the pointer on the state.data.questions
                    //hard coded: 2 because the number of pages before the first
                    //question shows up
                    this.store.data.questionGroups[i].questions[q].pointer = counter + 2;

                    //TODO move all this code to a better place!!
                    //TODO find a bug: opinion_scale type -> it saves next question with an empty array!!
                    //*INSERT ALL QUESTIONS IN THE  'validateQuestions' ARRAY*//
                    //*WITH A FLAG: 'required' AND 'was_answered':  *//
                    let questionData = groups[i].questions[q];
                    //only if the question has the required property true
                    if (questionData.required || true) {
                        /** TODO IMPORTANT remove true **/
                            //only the first two groups set the required value to 'true'
                        let required = true;
                        if (i == 0) {
                            required = true;
                        } else {
                            required = false;
                        }

                        let questionValidation = {
                            'required': required,  //questionData.required, /**TODO IMPORTANT remove comment**/
                            'was_answered': false,
                            'questionId': questionData.id, //used to the validation process
                            'pointer': counter + 2,//one Start page and the first group page
                        };
                        Vue.set(this.store.validateQuestions, questionData.id, questionValidation);

                    }


                } else {
                    //end of the group array
                    if (groupLength > i + 1) {
                        returnArray[counter] = ['group', i + 1,];
                        counter++
                        returnArray[counter] = ['inter_page', i + 1];

                    } else {
                        //add Formal page here
/*                        returnArray[counter] = ['formal', i, 1];
                        counter++
                        returnArray[counter] = ['inter_page', i];
                        counter++
                        returnArray[counter] = ['formal', i, 2, {'questionId': ''}];
                        counter++
                        returnArray[counter] = ['inter_page', i];
                        counter++
                        returnArray[counter] = ['final', i, {'questionId': ''}];*/
                    }
                }
                counter++;
            }
        }
        //add the Start(for the page) to the array or other values(pages wherever need it)

        returnArray.splice(0, 0, ['start', 0]);
        counter++;
        returnArray.splice(1, 0, ['inter_page', 0, 0])
        counter++;
        returnArray.splice(2, 0, ['group', 0, 0])
        counter++;
        returnArray.splice(3, 0, ['inter_page', 0, 0])
        this.store.matchList = returnArray;

        //set the category icon if available
        if (this.store.data.category && (this.store.data.category.icon)) {
            this.store.categoryIcon = this.store.data.category.icon;
        }
        //set the questionnaire icon if available
        if (this.store.data && (this.store.data.icon)) {
            this.store.questionnaireIcon = this.store.data.icon;
        }
        //set the if the user was filled out the questionnaire
        if (this.store.data && (this.store.data.user && (this.store.data.user.times) && (this.store.data.user.date))) {
            this.store.user = this.store.data.user;
        }


        return this.store;
    }

    public getMatchListForNavigation() {

        //get the match list
        let matchList = this.store.matchList;
        let toReturn: any = [];
        //iterate over to strip the odd pages(inter page)
        matchList.forEach((value: any, index: any) => {
            //use modulo to retrieve only even pages
            if (index % 2 == 0 && index != 0) {

                toReturn.push({
                    pointer: index,
                    value: value,
                });
            }
        });
        return toReturn;
    }

    public getMatchListForProgressBar(state: object) {

        //get the match list
        let arrayToReturn = {};
        //let matchList = state.matchList;
        // @ts-ignore


        return arrayToReturn;
    }

    public removeItemInArrayByValue(arrayData: any, key: string) {
        // return arrayData.filter( (letter:any) => letter != key);
    }

    public updatePointer(navObj: any) {
        //set the pointer first
        this.setPointerIndex(navObj); //navObj: { flag: string, pointer: number } 'back', 'next', 'nav'

        //find the indexes(group, question) in the match table in order to retrieve the page to be showed
        //returns an object with classAnimate and the indexes array

        let returnObject: any = {};
        returnObject.arrayIndexes = this.store.matchList[this.store.pointerIndex];
        returnObject.classAnimate = this.store.classAnimate
        return returnObject;
    }

    private static countCharsInOptions(choices: any, baseNumberOfChars: number, numberOfChoices: number) {

        if (typeof choices !== 'object') {
            return 0;
        }
        let totalChars = 0;
        let linesPerChoiceDesktop = 0;
        let linesPerChoiceMobil = 0;
        for (let key in choices) {
            let tempObj = choices[key];
            // @ts-ignore
            if (tempObj.hasOwnProperty('choice')) {
                //if row has less than 80 then set 80 minimum
                //try to figured out how to set a rule
                //desktop
                if (tempObj.choice.length > 80) {
                    //calculating the number of chars per choice that fit in one line
                    linesPerChoiceDesktop += Math.ceil(tempObj.choice.length / 80);
                }
                //mobil
                if (tempObj.choice.length > 30) {
                    //calculating the number of chars per choice that fit in one line
                    linesPerChoiceMobil += Math.ceil(tempObj.choice.length / 30);
                }

                totalChars += tempObj.choice.length;

            }
        }


        let desktop = null;
        let mobil = null;
        //check if the base number of chars is bigger than the total chars
        //in this case the meaning is that the number of choices must be consider
        if (baseNumberOfChars >= totalChars) {
            return {
                desktop: numberOfChoices - 4,
                mobil: numberOfChoices - 4,
            }
        } else {

            //in this case must take a look in to the
            //number of extra lines ->  linesPerChoiceDesktop
            //only check if there is mor than 2 because approx. in
            //the case only grows 5px but if there is third grows 16px each extra row
            if (linesPerChoiceDesktop > 2) {
                //40 px is the grow rate
                desktop = Math.ceil((linesPerChoiceDesktop - 1) / 2);
            } else {
                desktop = numberOfChoices - 4;
            }
            if (linesPerChoiceMobil > 2) {
                mobil = Math.ceil((linesPerChoiceMobil - 1) / 2);
            } else {
                mobil = numberOfChoices - 4;
            }


        }


        return {
            desktop: desktop,
            mobil: mobil,
        }

    }

    private setPointerIndex(navObj: any) {
        //check from where comes from the number(next btn, prev btn or navigation)
        let number = null;
        switch (navObj.flag) {
            case 'next':
                number = this.store.pointerIndex + 1;
                this.store.classAnimate = 'animate-questionnaire-next'
                break;
            case 'back':
                number = this.store.pointerIndex - 1;
                this.store.classAnimate = 'animate-questionnaire-back'
                break;
            case 'nav': // it comes from the navigation or from the validation
                number = navObj.pointer;
                break;
            default:
                number = 1
        }
        this.store.pointerIndex = number;
    }

    private static countChars(question: string) {

        //base number of chars that fit in mobil window
        let baseChars = 250;
        if (baseChars < question.length) {
            return Math.ceil((question.length - baseChars) / 30);
        } else {
            return false;
        }

    }
}