import Vue from "vue";
import axios from "axios";
import {StoreHelper} from "../Utils/StoreHelper";
import {ValidationHelper} from "../Utils/ValidationHelper";


export default {
    namespaced: true,
    state: {
        userType: null,
        user: null, //to flag if the user has already filled out the questionnaire
        pagesVisited: [],
        classAnimate: 'animate-questionnaire-next',
        categoryIcon: null, //store the value from the category: icon prop.(come from the ajax request) when available
        questionnaireIcon: null, //store the value from the questionnaire: icon prop.(come from the ajax request) when available
        eventBus: new Vue(),
        switchFlagNav: false, //flag to set and unset everytime that the navigation change the page
        validateQuestions: {},
        initializeValidation: false, //to turn on the validation -> when the user click the send btn
        data: {},
        loading: false,
        wasSaved: false,//this variable is going to be used at the end of the
        // questionnaire to find out if the questionnaire was correct saved in DB
        saving: false,
        error: {}, //it is used to pass the errors through the app
        errorsToDisplay: null,
        question: null,
        pointerIndex: 0,//contains the current pointer: matchList[pointerIndex]['name of the page','groupIndex','questionIndex']
        currentQuestion: null, //used to get the current questionId->it will be set when the question is rendered(validation)
        pointerData: {
            'groupIndex': 0,
            'questionIndex': 0
        },
        //var to retrieve the navigation(pointer)-> allows to navigates over the questionnaire
        navPointer: {
            'groupIndex': 0,
            'questionIndex': 0
        },

        matchList: [],
        results: {
            questionnaireId: null,
            evaluation: null,
            formal: [],
            questions: {},
        },

    },
    mutations: {
        //set error to display
        setErrorToDisplay(state: any, dataError: any) {
            state.errorsToDisplay = dataError;
        },
        //set wasSaved
        setWasSaved(state: any, valueData: any) {
            state.wasSaved = valueData;
        },

        //set pages visited: to record what pages were already visited for the user
        setPagesVisited(state: any, data: number) {
            state.pagesVisited.push(data);
        },

        //set the SwitchFlagNav
        setSwitchFlagNav(state: any, data: boolean) {
            state.switchFlagNav = data;
        },

        haveError(state: any) {
            return state.error !== null
        },

        //activate the validation
        activateValidation(state: any) {
            state.initializeValidation = true;
        },
        //assigning the question id
        setCurrentQuestion(state: any, questionId: number) {
            state.currentQuestion = questionId;
        },
        setData(state: any, data: any) {
            state.data = data;
            //save the questionnaireId on the results object
            state.results.questionnaireId = state.data.id;

            if (state.data && (state.data.id)) {
                state.userType = state.data.type;
            }
            //create the matchList and set the validateQuestions
            let helper = new StoreHelper(state);
            /*** In order to insert a new page into the navigation it is needed to verify where,
             * create new component page,
             * insert the page also in getDataToShow ***/

            //verify the type of user
            if (state.userType) {
                switch (state.userType) {
                    case 'school_board':
                        state = helper.getMatchListBoard();
                        break;
                    case 'school':
                        state = helper.getMatchList();
                        break;
                }

            }

        },
        setIsLoading(state: any, data: boolean) {
            state.loading = data;
        },
        // set the value of the saving property
        setIsSaving(state: any, value: boolean) {
            state.saving = value;
        },

        //set the evaluation that comes from the final part of the questionnaire-> when the
        // user gives an evaluation number
        setEvaluation(state: any, evaluation: any) {
            state.results.evaluation = evaluation;
        },
        //set the formal that comes from the Formal part of the questionnaire
        setFormal(state: any, value: any) {
            state.results.formal = value;
        },
        changeIndex(state: any, pointer: { groupIndex: any, questionIndex: any }) {
            if (pointer.groupIndex !== null) {
                state.pointer.groupIndex = pointer.groupIndex;
                state.pointer.questionIndex = -1;
            }
            if (pointer.questionIndex !== null) {
                state.pointer.questionIndex = pointer.questionIndex;
            }
        },
        setQuestionnaireData(state: any, questionData: { id: number, answer: object }) {
            //save the answer in the store -> state.result.question
            // @ts-ignore

            Vue.set(state.results.questions, questionData.id, questionData.answer);
            /**************************************
             * VALIDATION:
             * on a single question or all questions
             *The StoreHelper must have always the current state of the store
             * ****/
                // @ts-ignore
            let key_ = questionData.answer.questionId;
            //change the status of the question was_answered if the value is not null
            // @ts-ignore
            if (typeof questionData.answer.value == 'number') {
                /**OPINION_SCALE AND SINGLE**/
                Vue.set(state.results.questions[questionData.id], 'was_answered', true);
                //radio Btn s can not be unchecked -> in order to avoid scenarios where the answer was selected and
                // unselected and that will lead to flag the 'was_answered' with an empty value,
                // which in this case means
                // that the question was not answered


                //delete error by id : from the component 'opinion_scale' and 'multiple: single' because they are numbers
                //as a value
                //call a function from the StoreHelper
                //when the question is answered go to tne next page

                if (state.error[key_]) {
                    delete state.error[key_];

                    /**Differentiates if the must go to the next page or to the next question with error**/
                    //TO KNOW IF THE SEND BTN WAS PRESSED:-> validateAll
                    if (state.initializeValidation) {
                        //JUMP TO THE NEXT ERROR QUESTION AND
                        //ask if there are errors
                        //send the user to the first question in the array with the pointer
                        for (let key in state.error) {

                            let itemObject = state.error[key];
                            if (itemObject.hasOwnProperty('pointer')) {


                                setTimeout(function () {
                                    //go to next question

                                    state.pointerIndex = itemObject.pointer;
                                    //pass the flag and the pointer to the helper function
                                    let helper = new StoreHelper(state);

                                    //this object contains an array to pass to the pointerData:-> this update the page=changes the page
                                    let updatedObject = helper.updatePointer({
                                        flag: 'nav',
                                        pointer: state.pointerIndex
                                    });
                                    let arrayIndexes = updatedObject.arrayIndexes;
                                    //set once again the class for the animation
                                    state.classAnimate = updatedObject.classAnimate;

                                    //wait a few moments in order that the user can see
                                    //which option was selected: -> visual effect only
                                    state.pointerData.groupIndex = arrayIndexes[1];
                                    state.pointerData.questionIndex = arrayIndexes[2];

                                }, 500);
                                break;
                            }
                        }

                    } else {
                        //THE SEND BTN WAS NOT PRESSED YET
                        //go to the next question
                        setTimeout(function () {
                            //go to next question
                            let helper = new StoreHelper(state);

                            //this object contains an array to pass to the pointerData:-> this update the page=changes the page
                            let updatedObject = helper.updatePointer({flag: 'next'});
                            let arrayIndexes = updatedObject.arrayIndexes;
                            //set once again the class for the animation
                            state.classAnimate = updatedObject.classAnimate;


                            //wait a few seconds in order that the user see
                            //which option was selected: -> visual
                            state.pointerData.groupIndex = arrayIndexes[1];
                            state.pointerData.questionIndex = arrayIndexes[2];

                        }, 500);
                    }


                }


                // @ts-ignore
            } else if (typeof questionData.answer.value == 'object') {

                // @ts-ignore
                if (questionData.answer.value.length > 0) {

                    //ask if the other choice was selected and the property 'otherChoice' is not empty
                    // @ts-ignore
                    if (questionData.answer.value.includes(-1)) {
                        // @ts-ignore
                        if (questionData.answer.otherChoice && questionData.answer.otherChoice != '') {
                            //TODO simplify this code
                            //multiple choice can be and empty array when all checkbox are unchecked
                            /**MULTIPLE**/
                            if (state.error[key_]) {
                                delete state.error[key_];
                            }
                            Vue.set(state.results.questions[questionData.id], 'was_answered', true);
                            Vue.set(state.validateQuestions[questionData.id], 'was_answered', true);
                        } else {
                            //in case that the user was check and uncheck the checkbox: it is needed to
                            //to the final validation
                            Vue.set(state.results.questions[questionData.id], 'was_answered', false);
                            Vue.set(state.validateQuestions[questionData.id], 'was_answered', false);
                        }
                    } else { // @ts-ignore
                        if (questionData.answer.value.includes('null')) {

                            //verify if the reason was given

                            // @ts-ignore
                            if (questionData.answer.reason && (questionData.answer.reason != '')) {
                                if (state.error[key_]) {
                                    delete state.error[key_];
                                }


                                Vue.set(state.results.questions[questionData.id], 'was_answered', true);
                                Vue.set(state.validateQuestions[questionData.id], 'was_answered', true);

                            } else {

                                Vue.set(state.results.questions[questionData.id], 'was_answered', false);
                                Vue.set(state.validateQuestions[questionData.id], 'was_answered', false);

                            }


                        } else {
                            //multiple choice can be and empty array when all checkbox are unchecked
                            /**MULTIPLE**/
                            if (state.error[key_]) {
                                delete state.error[key_];
                            }


                            Vue.set(state.results.questions[questionData.id], 'was_answered', true);
                            Vue.set(state.validateQuestions[questionData.id], 'was_answered', true);

                        }
                    }


                } else {
                    Vue.set(state.results.questions[questionData.id], 'was_answered', false);
                    Vue.set(state.validateQuestions[questionData.id], 'was_answered', false);
                }
                // @ts-ignore
            } else if (typeof questionData.answer.value == 'string') {
                /**LONG_TEXT**/
                if (state.error[key_]) {
                    delete state.error[key_];
                }

                // @ts-ignore
                if (!questionData.answer.value == '') {
                    Vue.set(state.results.questions[questionData.id], 'was_answered', true);
                    Vue.set(state.validateQuestions[questionData.id], 'was_answered', true);
                } else {
                    Vue.set(state.results.questions[questionData.id], 'was_answered', false);
                    Vue.set(state.validateQuestions[questionData.id], 'was_answered', false);
                }


            }

        },
        //it is used for navigations purposes
        setPointer(state: any, indexArray: any) {
            //the first value of the array is the group index
            //the second value of the array is the question index
            state.pointerData.groupIndex = indexArray[1];
            state.pointerData.questionIndex = indexArray[2];
        },

        //insert manually an error to the 'error'
        setErrorToQuestion(state: any, questionId: number) {
            Vue.set(state.error, questionId, {pointer: -1});
        }

    },
    getters: {
        //get the number of groups
        getNumberOfGroups(state: any) {
            return state.data.questionGroups;
        },
        getUserType(state: any) {
            return state.userType;
        },
        //get category icon if available
        getCategoryIcon(state: any) {
            return state.categoryIcon;
        },
        //get questionnaire icon if available
        getQuestionnaireIcon(state: any) {
            return state.questionnaireIcon;
        },


        //get was saved
        getWasSaved(state: any) {
            return state.wasSaved;
        },
        //get category class for styling purposes
        getCategoryClass(state: any) {
            if (state.data && (state.data.category && (state.data.category.id))) {
                let categoryType = 'not_assign'
                let categoryId = state.data.category.id;
                switch (categoryId) {
                    case 1: //Vision und Ziele
                        categoryType = 'q-category-1';
                        break;
                    case 2: //Leadership
                        categoryType = 'q-category-2';
                        break;
                    case 3: //Personalentwicklung
                        categoryType = 'q-category-3';
                        break;
                    case 4: //Schulische Ausstattung
                        categoryType = 'q-category-4';
                        break;
                    case 5: //Weitere Lernorte
                        categoryType = 'q-category-5';
                        break;
                    case 6: //schulisches Lernen
                        categoryType = 'q-category-6';
                        break;
                }
                return categoryType;
            } else {
                return false;
            }
        },

        //retrieve pages visited
        getPagesVisited(state: any) {
            return state.pagesVisited;
        },
        //get switchFlagNav
        getSwitchFlagNav(state: any) {
            return state.switchFlagNav;
        },

        //get current question
        getCurrentQuestion(state: any) {
            return state.currentQuestion;
        },

        //get is saving variable to show a spinner when the questionnaire is sent to the endpoint
        getIsSaving(state: any) {
            return state.saving;
        },

        //it retrieves the match list for the navigation bar(squares)
        getMatchListNavigation(state: any) {

            //the odd pages are always  inter pages the do not need to be included
            //the idea is to go to a prev page(inter page) and will land in the selected question
            //call the StoreHelper
            let helper = new StoreHelper(state);
            return helper.getMatchListForNavigation();
        },
        //retrieves the match list for the progress bar(lines)
        getMatchListForProgressBar(state: any) {
            if (!state.loading) {
                let groups = state.data.questionGroups;
                let arrayToReturn = {};

                let matchList = state.matchList;
                let numberOfGroupsInData = state.data.questionGroups;
                for (let group in numberOfGroupsInData) {
                    let tempArray = [];
                    for (let key in matchList) {
                        // @ts-ignore
                        if (key % 2 != 0 || matchList[key][0] == 'start') {
                            continue;
                        }
                        if (matchList[key][1] == group) {
                            tempArray.push(matchList[key]);
                            //Vue.set(arrayToReturn,group,page);
                        }
                        Vue.set(arrayToReturn, group, tempArray);
                    }
                }
                return arrayToReturn;
            }


        },
        //it retrieves the current value of the class for the animation
        getClassAnimate(state: any) {
            return state.classAnimate;
        },
        //get the length of the match list
        getLengthMatchList(state: any) {
            return state.matchList.length;
        },
        //Keyboard events
        getEventBus(state: any): any {
            return state.eventBus;
        },
        //it retrieves the value of the initializeValidation variable: to flag if the send btn was clicked
        getInitializeValidation(state: any) {
            return state.initializeValidation;
        },

        getPropertyError(state: any) {
            return state.error;
        },

        getErrors: (state: any) => (id: number) => {
            //iterate over the error array
            if (state.error[id]) {
                return true;
            } else {
                return false;
            }
        },

        getErrorToDisplay(state: any) {
            return state.errorsToDisplay;
        },
        getPointer(state: any) {
            return state.pointerIndex;
        },
        getDataToShow(state: any) {
            //check which page is going to be displayed and give the data
            if (state.matchList.length < 1) {
                return false;
            }
            let index = state.matchList[state.pointerIndex];
            let page = [];
            switch (index[0]) {
                case 'inter_page':
                    page.push(index[0]);//add the name of the page which is on the first element of the array
                    page.push({
                        'questionnaireId': state.data.id,
                        'type': state.data.type,
                        'name': state.data.name,
                        'fullName': state.data.fullName,
                        'category': state.data.category,
                    });
                    page.push({
                        //category position: in order to know in what part of the questionnaire we are.
                        //for the VisualNavigation component
                        //we need the position of the category in all pages.
                        'catPosition': state.data.questionGroups[state.pointerData.groupIndex].position,
                    });
                    break;
                case 'start':
                    //set user if has already filled out the questionnaire
                    let user = null;
                    if (state.data.user) {
                        user = {wasHere: true, times: state.data.user.times, lastdate: state.data.user.date}
                    }

                    page.push(index[0]);//add the name of the page which is on the first element of the array
                    page.push({
                        'questionnaireId': state.data.id,
                        'schooltype': state.data.schooltype,
                        'type': state.data.type,
                        'name': state.data.name,
                        'fullName': state.data.fullName,
                        'category': state.data.category,
                        'user': user
                    });
                    page.push({
                        //category position: in order to know in what part of the questionnaire we are
                        //we need the position of the category in all pages
                        'catPosition': state.data.questionGroups[state.pointerData.groupIndex].position,
                    });
                    break;
                case 'group':
                    page.push(index[0]);//add the name of the page which is on the first element of the array
                    page.push({
                        'id': state.data.questionGroups[state.pointerData.groupIndex].id,
                        'name': state.data.questionGroups[state.pointerData.groupIndex].name,
                        'description': state.data.questionGroups[state.pointerData.groupIndex].description,
                    });
                    page.push({
                        //category position: in order to know in what part of the questionnaire we are
                        //we need the position of the category in all pages
                        'catPosition': state.data.questionGroups[state.pointerData.groupIndex].position,
                    });
                    break;
                case 'question':
                    let questionData = state.data.questionGroups[state.pointerData.groupIndex].questions[state.pointerData.questionIndex];
                    //set the question id to the state variable
                    state.currentQuestion = questionData.id;
                    page.push(index[0]);//add the name of the page which is on the first element of the array
                    page.push({
                        'question': questionData,
                    });
                    page.push({
                        //category position: in order to know in what part of the questionnaire we are
                        //we need the position of the category in all pages
                        'catPosition': state.data.questionGroups[state.pointerData.groupIndex].position,
                    });
                    break;
                case 'formal':
                    page.push(index[0]);//add the name of the page which is on the first element of the array
                    page.push({
                        'pageNumber': index[2],
                        'questionnaireId': state.data.id,
                        'type': state.data.type,
                        'name': state.data.name,
                        'fullName': state.data.fullName,
                        'category': state.data.category,
                    });
                    page.push({
                        //category position: in order to know in what part of the questionnaire we are
                        //we need the position of the category in all pages
                        'catPosition': state.data.questionGroups[state.pointerData.groupIndex].position,
                    });
                    break;
                case 'final':
                    page.push(index[0]);//add the name of the page which is on the first element of the array
                    page.push({
                        'questionnaireId': state.data.id,
                        'type': state.data.type,
                        'name': state.data.name,
                        'fullName': state.data.fullName,
                        'category': state.data.category,
                    });
                    page.push({
                        //category position: in order to know in what part of the questionnaire we are
                        //we need the position of the category in all pages
                        'catPosition': state.data.questionGroups[state.pointerData.groupIndex].position,
                    });
                    break;


            }
            return page;
        },
        getCategoryId(state: any) {
            if (state.data.category) {
                return state.data.category.id;
            }
        },
        getPosition(state: any) {
            return state.data.position;
        },
        getPointerIndex(state: any) {
            return state.pointerIndex;
        },
        getMatchList(state: any) {
            return state.matchList;

        },
        getData(state: any) {
            return state.data;
        },
        isLoading(state: any) {
            return state.loading;
        },
        getCurrentType(state: any) {
            return (state.pointer.questionIndex < 0) ? "group" : "question";
        },
        getCurrentData(state: any) {
            //if state pointer questionIndex <0
            if (state.pointer.questionIndex < 0) {
                return state.data.questionGroups[state.pointer.groupIndex];
            } else {
                return state.data.questionGroups[state.pointer.groupIndex].questions[state.pointer.questionIndex];
            }
        },
        getQuestion(state: any) {
            return state.data.questionGroups[state.pointer.groupIndex].questions[state.pointer.questionIndex];
        },
        getResult(state: any) {
            return state.results;
        },
        getValidateQuestions(state: any) {
            return state.validateQuestions;
        },
    },
    actions: {
        //dispatch pagesVisited
        insertVisitedPage({commit}: any, page: number) {
            commit("setPagesVisited", page);
        },

        //change the flag for the navigation
        changeSwitchFlagNav({commit}: any, navFlag: boolean) {

            commit("setSwitchFlagNav", navFlag);

        },

        //validation of the current question
        validateCurrentQuestion({commit, state}: any, questionId: number) {
            let valHelper = new ValidationHelper(state);
            if (valHelper.validateOne(questionId)) {
                commit("setErrorToQuestion", questionId)
            }
        },

        validateQuestionnaire({commit, state}: any) {
            //set the flag that indicates the the validator has started: btn 'Senden' was pressed
            state.initializeValidation = true;
            let helper = new ValidationHelper(state);
            state.error = helper.validateAll();
            //state.error is an Object: we need to know if it contain something that represent an error in a question and then
            //send the user to the first question in the array with the pointer
            for (let key in state.error) {

                let itemObject = state.error[key];
                if (itemObject.hasOwnProperty('pointer')) {
                    state.pointerIndex = itemObject.pointer;
                    let indexArray = state.matchList[state.pointerIndex];
                    commit("setPointer", indexArray);
                    return false;
                }

            }

            //go to the end of the questionnaire
            let helper_store = new StoreHelper(state);
            let updatedObject = helper_store.updatePointer({flag: 'nav', pointer: state.matchList.length - 1});
            let arrayIndexes = updatedObject.arrayIndexes;
            //set once again the class for the animation
            state.classAnimate = updatedObject.classAnimate;

            //let indexArray = state.matchList[state.matchList.length - 1];
            commit("setPointer", arrayIndexes);


        },

        saveCurrentQuestionId({commit}: any, questionId: number) {
            commit('setCurrentQuestion', questionId);
        },

        /**SENDING THE QUESTIONNAIRE TO BACKEND TO BE SAVED IN THE DATABASE**/
        saveQuestionnaire({commit}: any, questionnaireData: any) {

            let questions = {};
            let result = {};

            for (let key in questionnaireData.questions) {
                let tempObj = questionnaireData.questions[key];
                if (typeof tempObj.value == 'object' &&
                    (tempObj.value.includes(-1))) {
                    result = {
                        questionId: tempObj.questionId,
                        value: tempObj.value,
                        otherChoice: tempObj.otherChoice,
                    };
                } else if (typeof tempObj.value == 'object' &&
                    (tempObj.value.includes('null'))) {
                    result = {
                        questionId: tempObj.questionId,
                        value: null,
                        reason: tempObj.reason,
                    };
                } else {
                    result = {
                        questionId: tempObj.questionId,
                        value: tempObj.value,
                    }
                }
                Vue.set(questions, tempObj.questionId, result);

            }
            let dataToSave = {};

            //if school authority(school_board)
            if (questionnaireData.userType && questionnaireData.userType === 'school_board') {
                dataToSave = {
                    questionnaireId: questionnaireData.questionnaireId,
                    rating: questionnaireData.evaluation,
                    formal: questionnaireData.formal,
                    share: questionnaireData.formal,
                    questions: questions,
                    userType: questionnaireData.userType,
                }
            } else {
                dataToSave = {
                    questionnaireId: questionnaireData.questionnaireId,
                    rating: questionnaireData.evaluation,
                    share: questionnaireData.formal,
                    questions: questions,
                }
            }


            //commit("setIsSaving", true);
            axios.post('/Questionnaire/save', {
                questionnaireData: dataToSave,
            }).then(response => {
                //based on the response here is going to be assigned the variable 'wasSaved' to true or false
                //is going to serves to determinate if the questionnaire was correctly saved
                //assign the value to the store variable wasSaved

                if (response.data && (response.data === 'SAVED')
                    && response.status && (response.status == 200)) {
                    //commit("setIsSaving", false);
                    commit("setWasSaved", true);
                    //set time out in order that the user can see the result
                    setTimeout(function () {
                        commit("setIsSaving", true);
                        //window.location.href = '/auswertung';
                        window.location.href = '/PublicUser/user-success';
                    }, 2000);


                    commit("setIsSaving", false);
                } else {
                    //set error to display
                    let msg = 'Beim Speichern des Fragebogens ist ein Fehler aufgetreten. Bitte überprüfen Sie, ob Sie angemeldet sind, oder wenden Sie sich an den Administrator.';
                    commit("setErrorToDisplay", msg);

                }
            }).catch(e => {
                commit("setIsSaving", false);
            });
        },

        loadQuestionnaire({commit}: any, questionnaireId: number) {
            commit("setIsLoading", true);
            axios.get('/Questionnaire/get/' + questionnaireId).then(response => {
                commit("setData", response.data[0]);
                commit("setIsLoading", false);
            }).catch(e => {
                commit("setIsLoading", true);
            });
        },
        //Just for the navigation component
        updatePointerFromNav({commit, state}: any, pointer: number) {
            //retrieve the pointer
            // state.pointerIndex = pointer;
            //set the pointer to the previous page(inter page)to allow the animation -> next
            state.pointerIndex = pointer - 1;
            let arrayIndexes = state.matchList[state.pointerIndex];
            commit("setPointer", arrayIndexes);
        },

        //It changes the value of the pointer coming from the components
        //and match it with the matchList and retrieves the indexes from it:
        //-> match table: array[pointer] -> [0] indexGroup, [1] indexQuestion
        updatePointer({commit, state}: any, navObj: { flag: string, pointer: number }) {

            let helper_store = new StoreHelper(state);
            let updatedObject = helper_store.updatePointer(navObj);
            let arrayIndexes = updatedObject.arrayIndexes;
            //set once again the class for the animation
            state.classAnimate = updatedObject.classAnimate;
            commit("setPointer", arrayIndexes);

        },
        passEvaluation({commit}: any, evaluation: number) {
            commit("setEvaluation", evaluation);
        },
        //it is going to work with the ok btn
        setValidationError({commit}: any, questionId: number) {
            commit("setErrorToQuestion", questionId)
        },


    }
}