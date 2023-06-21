import Vue from "vue";

/**This class is in charge of the whole validation process of the questionnaire
 *
 *
 * **/

export class ValidationHelper {

    protected storeData: any;
    private questionsInfo: any; // iterate over the questionnaire, find the questions and save all of them in this variable


    public constructor(storeData: any) {
        this.storeData = storeData;
    }

    public validateOne(questionId: any) {

        //check if there are requirements for the question: is required
        //find the question by id
        //compare if the question was answered
        let questionsResults = this.storeData.results.questions;
        //retrieve the result variable in order to see if the question was answered already
        if (questionsResults[questionId]) {
            //iterate over the storeData to find out if the question has the required property true
            //and in this case return true(error) if the question was not answer
            if (!questionsResults[questionId].was_answered && this.isRequired(questionId) ) {
                return true;
            }
        } else {
            if (this.isRequired(questionId)) {
                return true;
            }

        }


    }

    public validateAll() {
        //this function is run by the component Final, when the 'Senden' Btn is pressed
        //it verifies if there are questions that have the required property -> true and also
        //if those were not answered.
        //No id is provided because the function will iterate over all questions in the questionnaire
        //looking for those questions that have a required property and was not answered
        return this.isRequiredAll();


    }

    private isRequiredAll() {

        let toReturn = [];

        let requiredQuestions = this.storeData.validateQuestions;
        //check if the required question was answered
        //search in the variable 'results.questions' if the question is there and was answered
        //with a not empty value, because there are question that could be checked and the unchecked: the store
        //is saving everytime that a input is checked.
        let answeredQuestions = this.storeData.results.questions;
        //iterate over the requireQuestions
        for (let key in requiredQuestions) {
            //check if the question was saved on the results.questions
            //first check if the question is required
            if(requiredQuestions[key].required){
                //if was saved in the store
                if (answeredQuestions[key]){
                    if (!answeredQuestions[key].was_answered){
                        Vue.set(this.storeData.error, requiredQuestions[key].questionId, {
                            'questionId': requiredQuestions[key].questionId,
                            'pointer': requiredQuestions[key].pointer,
                        });
                    }
                }else{
                    //not saved in store yet: -> set the error
                    Vue.set(this.storeData.error, requiredQuestions[key].questionId, {
                        'questionId': requiredQuestions[key].questionId,
                        'pointer': requiredQuestions[key].pointer,
                    });
                }

            }
/*
            if (answeredQuestions[key]) {

                //just aks if the question was answered
                if (!answeredQuestions[key].was_answered && requiredQuestions[key].required) {
                    Vue.set(this.storeData.error, requiredQuestions[key].questionId, {
                        'questionId': requiredQuestions[key].questionId,
                        'pointer': requiredQuestions[key].pointer,
                    });
                }

            } else {
                Vue.set(this.storeData.error, requiredQuestions[key].questionId, {
                    'questionId': requiredQuestions[key].questionId,
                    'pointer': requiredQuestions[key].pointer,
                });

            }*/
        }

        return this.storeData.error;


    }

    private isRequired(id: any) {
        return this.storeData.validateQuestions[id].required;
    }


}