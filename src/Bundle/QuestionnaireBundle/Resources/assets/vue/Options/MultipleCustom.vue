<template>
  <div>
    <h4 class="q-color-primary pb-2">{{ getQuestion.question }}</h4>
    <b-form-group v-slot="{ ariaDescribedby }" class="position-relative">
      <b-form-checkbox-group
          :disabled="propDisabled"
          id="checkbox-group-1"
          v-model="selection"
          :options="getChoices"
          :aria-describedby="ariaDescribedby"
          name="multiple_question"
          class="d-flex flex-column"
          ref="q_parent_checkbox_group"
          :class="classDisabled"
      ></b-form-checkbox-group>
      <b-form-input id="q_other_choice" ref="q_other_choice_ref"
                    type="text"
                    v-model="otherChoice"
                    placeholder="Geben Sie Ihre Option ein."
                    :class="classInput"
      >
      </b-form-input>
    </b-form-group>

    <comp-reason-no-answer :question-id="getQuestionId"></comp-reason-no-answer>

    <ok-btn :onclicked="actionBtn" :status-class="hasSelectedAQuestion"></ok-btn>
    <error v-if="getQuestionError && !hasSelectedAQuestion" :message="errorMsg?errorMsg:null"></error>

  </div>
</template>

<script>
import OkButton from "./OkButton";
import {QuestionnaireHelper} from "../Utils/QuestionnaireHelper";
import Error from "./childcomponents/Error";
import CompReasonNoAnswer from "./childcomponents/CompReasonNoAnswer";

export default {

  name: "MultipleCustom",
  props: {
    pointer: null,
    questionData: null,
  },
  data() {
    return {
      helper: null,
      errorMsg: null,
      labelOther: 'Andere',
      classInput: 'q-hide-input',
      classDisabled: null,
      questionError: null,
      classFlag:false, // to prevent the keyboard events when the checkbox group is disabled
      propDisabled: false,//to disabled the checkbox group
    }
  },
  components: {
    okBtn: OkButton,
    error: Error,
    CompReasonNoAnswer,

  },
  created() {
    //instantiate the QuestionnaireHelper
    this.$data.helper = new QuestionnaireHelper();
  },
  mounted() {
    //register the event in the component
    this.$store.getters["qstore/getEventBus"].$on('question_answer_letter', this.eventHandler);
    this.$store.getters["qstore/getEventBus"].$on('question_answer_iteration', this.eventHandler);

  },
  destroyed() {
    //unregister the event in the component
    this.$store.getters["qstore/getEventBus"].$off('question_answer_letter', this.eventHandler);
    this.$store.getters["qstore/getEventBus"].$off('question_answer_iteration', this.eventHandler);

  },
  watch: {
    otherChoice: function (new_value, old_value) {

      if (old_value != '' && new_value == '') {
        //  this.otherChoice = old_value;
      }


    },

    selection: function (new_value, old_value) {

      //if other is selected give focus to the input

      if (new_value.includes(-1) && !old_value.includes(-1)) {
        this.showInput();
      }

      if (!new_value.includes(-1)) {
        // this.hideInput();
      }


    },

  },
  computed: {
    //error msg
    getErrorMsg() {
      return this.errorMsg;
    },

    //get the input class
    getInputClass() {
      return this.classInput;
    },

    //verify if there is a selected value in order to give this flag to the ok btn to change the color
    hasSelectedAQuestion() {
      if (this.getResults.questions[this.getQuestionId] && (this.getResults.questions[this.getQuestionId].was_answered)) {
        return true;
      }
    },
    //retrieve the status of the property initializeValidation from the store
    startedInitializeValidation() {
      return this.$store.getters["qstore/getInitializeValidation"];
    },
    //it retrieves if this question has an error
    getQuestionError() {
      this.updateError();
      return this.questionError;
    },

    //get letters
    letters() {
      return this.$data.helper.getLetters();
    },
    //other choice: -> input text
    otherChoice: {
      get() {
        if (this.getResults.questions[this.getQuestionId]) {
          //check if the value contains -1: that means that other choice was selected
          if (this.getResults.questions[this.getQuestionId].value.includes(-1)) {
            this.showInput();
            return this.getResults.questions[this.getQuestionId].otherChoice;
          } else {
            this.hideInput();
            return '';
          }
        } else {
          return '';
        }
      },
      set(value) {
        //delete the error if exist
        if (this.errorMsg != '' && value != '') {
          this.errorMsg = null;
        }

        //save only if the other option was selected
        let answer = {};
        answer = {
          id: this.getQuestionId,
          answer: {
            questionId: this.getQuestionId,
            //questionType: this.getQuestionType,
            value: this.selection,
            otherChoice: value,
          }
        }
        this.$store.commit("qstore/setQuestionnaireData", answer);

      }

    },

    selection: {
      //it assigns the value to the checkboxes stored on the store if is available
      //otherwise return an empty array
      get() {
        if (this.getResults.questions[this.getQuestionId]) {
          return this.getResults.questions[this.getQuestionId].value;
        } else {
          return [];
        }

      },
      set(value) {

        if (!value.includes(-1)) {
          this.hideInput();
        }

        //save the value in the store
        let answer = {
          id: this.getQuestionId,
          answer: {
            questionId: this.getQuestionId,
            //questionType: this.getQuestionType,
            value: value,
            otherChoice: this.$refs['q_other_choice_ref'].value,
          }
        }
        //just to keep the value of the input
        if (this.otherChoice != '') {
          //answer.otherChoice = this.otherChoice;
        }

        this.$store.commit("qstore/setQuestionnaireData", answer);
        //retrieves the current state of the state.error variable
        this.updateError();
      },
    },
    //it retrieves the number of steps coming from the question properties -> steps
    getChoices() {
      return this.setChoices();
    },
    getQuestionType() {
      return this.getQuestion.type;
    },
    getQuestion() {
      return this.questionData;
    },
    //it retrieves the value to determinate if the displayed value must start at one(1)
    //returns true or false
    getStartAtOne() {
      return this.getQuestion.properties.start_at_one;
    },
    //it retrieves the number of steps coming from the question properties -> steps
    getSteps() {
      return this.getQuestion.properties.steps;
    },
    //it retrieves the labels that will come on the bottom of the options
    getLabels() {
      //TODO Verify if all the time comes the same number of labels elements
      return this.getQuestion.properties.labels;
    },
    getQuestionId() {
      return this.getQuestion.id;
    },
    getResults() {
      return this.$store.getters["qstore/getResult"]
    },

  },
  methods: {
    //switch the input to hide and show
    //active input
    showInput: function () {
      //set the class to show the input
      this.classInput = 'q-show-input'
      this.labelOther = '';
      // this.$refs['q_other_choice_ref'].focus();
    },
    hideInput() {
      this.classInput = 'q-hide-input';
      this.labelOther = "Andere";
    },

    //it updates the error
    updateError: function () {
      this.questionError = this.$store.getters["qstore/getErrors"](this.getQuestionId);
    },
    eventHandler: function (keyboardKey) {
      if (!this.classFlag) {
        //assign a letter to each input in order to be able to set a keyboard event to the input
        //match table from the QuestionnaireHelper
        let letters = this.$data.helper.getLetters();
        //this function is called when the letters are pressed: a,b,c,...
        let choices = this.getChoices;
        choices.forEach((value, index) => {

          if (letters[index] == keyboardKey) {
            //if it is empty
            if (this.selection.length < 1) {
              //this.$set(this, 'selection', [value.value]);
              this.selection = [];
              this.selection.push(value.value);
              //set the focus to the input
            } else {
              //uncheck the checkbox if already was selected
              if (!this.selection.includes(value.value)) {
                let arraySelection = this.selection;
                arraySelection.push(value.value);
                this.$set(this, 'selection', arraySelection);
                //this.selection.push(value.value);
              } else {
                this.selection = this.$data.helper.removeItemInArrayByValue(this.selection, value.value);
              }
            }


          }
        });
      }
      this.updateError();

      //this function is called when the Enter key is pressed
      if (keyboardKey == 'Enter') {
        this.actionBtn();
      }

    },
    setChoices: function () {
      let arrayReturn = [];
      let choices = this.getQuestion.choices;
      choices.forEach((value, index) => {
            arrayReturn.push({text: value.choice, value: value.id});
          }
      );
      //all at the final the 'allow_other_choice' option
      arrayReturn.push({text: this.labelOther, value: -1})
      return arrayReturn;
    },
    //controls the OK btn it validate the current question
    actionBtn: function () {
      //Validates the question

      if (this.getResults.questions[this.getQuestionId]){
        let questionR = this.getResults.questions[this.getQuestionId];
        if(questionR.value.includes('null') && (!questionR.reason)){
          this.errorMsg = 'Bitte eine Begründung schreiben.';
        }
        if (questionR.value.includes(-1) && (!questionR.otherChoice)){
          this.errorMsg = 'Bitte formulieren Sie eine Option.';
        }

      }else{
        //is not saved: user did not selected any yet
        this.errorMsg = 'Bitte wählen Sie eine Option.';
      }

      this.$store.dispatch("qstore/validateCurrentQuestion", this.getQuestionId);
      //update the status of the error for this question
      this.updateError();

      //differentiates if the initializeValidation was started: for have another behaviour-> is going to jump to the next error
      if (this.startedInitializeValidation) {
        //go to the next question with error
        //validate all again
        this.$store.dispatch("qstore/validateQuestionnaire");

      } else {
        //if no error go to the next page
        if (!this.getQuestionError) {
          this.$store.dispatch("qstore/updatePointer", {flag: 'next'});
        }//
      }


    },
  },

}
</script>

<style lang="scss" scoped>

div#checkbox-group-1 .custom-control-label {
  width: 100% !important;
}

.questionnaire {

  input#q_other_choice {
    position: absolute;
    bottom: 7px;
    z-index: 100;
    width: 83%;
    left: 43px;
    height: 29px;
    background: transparent;
    border: none;
    padding: 0;
    font-size: .8em;
  }

  /*class set when checkbox other is selected*/
  .q-show-input {
    display: block !important;
  }

  .q-hide-input {
    display: none;
  }


  div#checkbox-group-1 {

  }


  div#checkbox-group-1 div:nth-child(1) label::before {
    content: "A" !important;
  }

  div#checkbox-group-1 div:nth-child(2) label::before {
    content: "B";
  }

  div#checkbox-group-1 div:nth-child(3) label::before {
    content: "C";
  }

  div#checkbox-group-1 div:nth-child(4) label::before {
    content: "D";
  }

  div#checkbox-group-1 div:nth-child(5) label::before {
    content: "E";
  }

  div#checkbox-group-1 div:nth-child(6) label::before {
    content: "F";
  }

  div#checkbox-group-1 div:nth-child(7) label::before {
    content: "G";
  }

  div#checkbox-group-1 div:nth-child(8) label::before {
    content: "H";
  }

  div#checkbox-group-1 div:nth-child(9) label::before {
    content: "I";
  }

  div#checkbox-group-1 div:nth-child(10) label::before {
    content: "J";
  }

}

.questionnaire .q-category-5 div#checkbox-group-1 div:nth-child(1) label::before {
  background: rgba(70, 8, 59, 0.1);
  content: "A";
}

</style>