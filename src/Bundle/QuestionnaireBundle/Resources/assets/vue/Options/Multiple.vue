<template>
  <div>
    <h4 class="q-color-primary pb-2">{{ getQuestion.question }}</h4>
    <b-form-group v-slot="{ ariaDescribedby }">
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
    </b-form-group>

    <comp-reason-no-answer ref="q_child_reason" :question-id="getQuestionId"></comp-reason-no-answer>

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

  name: "Multiple",
  props: {
    pointer: null,
    questionData: null,
  },
  data() {
    return {
      errorMsg: null,
      helper: null,
      questionError: null,
      propDisabled: false,
      classDisabled: null,
      classFlag: false, // to prevent the keyboard events when the checkbox group is disabled
    }
  },
  components: {
    CompReasonNoAnswer,
    okBtn: OkButton,
    error: Error,

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
  computed: {
    //error msg
    getErrorMsg() {
      return this.errorMsg;
    },
    //verify if there is a selected value in order to give this flag to the ok btn to change the color
    //when true btn turns to the category color
    hasSelectedAQuestion() {
      //check if includes 'null' the selection var and the input 'q_other_reason_ref' has a string not empty
      if (this.getResults.questions[this.getQuestionId] && (this.getResults.questions[this.getQuestionId].was_answered)) {
        return true;
      }

      // return this.selection.length > 0 && !this.selection.includes('null');
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
        //save the value in the store
        let answer = {
          id: this.getQuestionId,
          answer: {
            questionId: this.getQuestionId,
            //questionType: this.getQuestionType,
            value: value
          }
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
        let choices = this.getQuestion.choices;
        choices.forEach((value, index) => {

          if (letters[index] == keyboardKey) {
            //if it is empty
            if (this.selection.length < 1) {
              this.selection = [];
              this.selection.push(value.id);
            } else {
              //uncheck the checkbox if already was selected
              if (!this.selection.includes(value.id)) {
                this.selection.push(value.id);

              } else {
                this.selection = this.$data.helper.removeItemInArrayByValue(this.selection, value.id);
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
      return arrayReturn;
    },
    //controls the OK btn it validate the current question
    actionBtn: function () {
      //Validates the question: ask if the 'null' is in array
      //switch

      if (this.getResults.questions[this.getQuestionId]) {
        let questionR = this.getResults.questions[this.getQuestionId];
        if (questionR.value.includes('null') && (!questionR.reason)) {
          this.errorMsg = 'bitte eine Begründung schreiben';
        }

      } else {
        //is not saved: user did not selected any yet
        this.errorMsg = 'Bitte wählen Sie eine Option';
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
        }
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