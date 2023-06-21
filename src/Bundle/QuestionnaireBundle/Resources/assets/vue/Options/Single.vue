<template>
  <div>
    <h4 class="q-color-primary pb-2">{{ getQuestion.question }}</h4>
    <b-form-group v-slot="{ ariaDescribedby }">
      <b-form-radio-group
          buttons
          id="radio-group-1"
          v-model="selection"
          :options="getChoices"
          :aria-describedby="ariaDescribedby"
          name="radio-options"
      ></b-form-radio-group>
    </b-form-group>

    <ok-btn :onclicked="actionBtn" :status-class="hasSelectedAQuestion"></ok-btn>
    <error v-if="getQuestionError"></error>

  </div>
</template>

<script>
import OkButton from "./OkButton";
import Error from "./childcomponents/Error";

export default {
  name: "Single",
  components: {
    okBtn: OkButton,
    error: Error,
  },
  props: {
    pointer: null,
    questionData: null,
  },
  data() {
    return {
      questionError: null,
    }
  },
  mounted() {
    this.$store.getters["qstore/getEventBus"].$on('question_answer_number', this.eventHandler);
    this.$store.getters["qstore/getEventBus"].$on('question_answer_iteration', this.eventHandler);


  },
  destroyed() {
    this.$store.getters["qstore/getEventBus"].$off('question_answer_number', this.eventHandler);
    this.$store.getters["qstore/getEventBus"].$off('question_answer_iteration', this.eventHandler);

  },
  computed: {
    //retrieve the status of the property initializeValidation from the store
    startedInitializeValidation() {
      return this.$store.getters["qstore/getInitializeValidation"];
    },
    //verify if there is a selected value in order to give this flag to the ok btn to change the color
    hasSelectedAQuestion() {
      return typeof this.selection === 'number';
    },
    getQuestionError() {
      this.updateError();
      return this.questionError;
    },

    selection: {
      get() {
        //it assigns the value to the radio btn stored on the store if is available
        //otherwise return an empty array
        if (this.getResults.questions[this.getQuestionId]) {
          return this.getResults.questions[this.getQuestionId].value;
        } else {
          return [];
        }
      },
      set(value) {
        //it is use to save the selection in the Store
        let answer = {
          id: this.getQuestionId,
          answer: {
            questionId: this.getQuestionId,
            //questionType: this.getQuestionType,
            value: value,
          }
        }
        this.$store.commit("qstore/setQuestionnaireData", answer);
        //retrieves the current state of the state.error variable
        this.updateError();
      }
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
    //it retrieves the number of steps coming from the question properties -> steps
    getSteps() {
      return this.getQuestion.properties.steps;
    },
    getQuestionId() {
      return this.getQuestion.id;
    },
    getResults() {
      return this.$store.getters["qstore/getResult"]
    },

  },
  methods: {
    updateError: function () {
      this.questionError = this.$store.getters["qstore/getErrors"](this.getQuestionId);
    },

    eventHandler: function (keyboardKey) {
      //this function is called when the number: 1 or 2 is pressed
      //it iterates over the choices of the radio btn and assign the value to
      //the variable 'selection' which controls the selected radio btn
      let choices = this.getQuestion.choices;
      choices.forEach((value, index) => {
        if (index + 1 == keyboardKey) this.selection = value.id;
      });
      //when the enter key is pressed must validate the question or go to the next page
      if (keyboardKey == 'Enter') {

        this.actionBtn();

      }
    },

    setChoices: function () {

      let arrayReturn = [];
      let choices = this.getQuestion.choices;

      choices.forEach((value, index) => {
            arrayReturn.push({text: value.choice, value: value.id, autofocus: true});
          }
      );
      return arrayReturn;
    },
    //ok btn validation
    actionBtn: function () {

      //Validates the question
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

<style scoped>
.gray {
  color: gray;
}
</style>