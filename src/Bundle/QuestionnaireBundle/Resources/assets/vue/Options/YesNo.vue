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
          class="q-yes-no"
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
  components: {
    okBtn: OkButton,
    error: Error,
  },
  name: "YesNo",
  props: {
    questionData: null,
  },
  data() {
    return {
      questionError: null,
    }
  },
  mounted() {
    //register the keyboard events
    this.$store.getters["qstore/getEventBus"].$on('question_answer_letter', this.eventHandler);
    this.$store.getters["qstore/getEventBus"].$on('question_answer_iteration', this.eventHandler);
  },
  destroyed() {
    //unregister the keyboard events
    this.$store.getters["qstore/getEventBus"].$off('question_answer_letter', this.eventHandler);
    this.$store.getters["qstore/getEventBus"].$off('question_answer_iteration', this.eventHandler);
  },
  computed: {
    //retrieve the status of the property initializeValidation from the store
    startedInitializeValidation() {
      return this.$store.getters["qstore/getInitializeValidation"];
    },
    getQuestionError() {
      this.updateError();
      return this.questionError;
    },
    //verify if there is a selected value in order to give this flag to the ok btn to change the color
    hasSelectedAQuestion() {
      return typeof this.selection === 'number';
    },

    selection: {
      get() {
        //verify if is there a answer on the store saved before: if so return the stored value otherwise -> return an empty array
        if (this.getResults.questions[this.getQuestionId]) {
          return this.getResults.questions[this.getQuestionId].value;
        } else {
          return null;
        }

      },
      set(value) {
        let answer = {
          id: this.getQuestionId,
          answer: {
            questionId: this.getQuestionId,
            //questionType: this.getQuestionType,
            value: value
          }
        }
        this.updateError();
        this.$store.commit("qstore/setQuestionnaireData", answer);
      },
    },
    getResults() {
      return this.$store.getters["qstore/getResult"]
    },
    getQuestionId() {
      return this.getQuestion.id;
    },
    getQuestionType() {
      return this.getQuestion.type;
    },
    getQuestion() {
      return this.questionData;
    },
    getChoices() {
      return [
        {text: 'Ja', value: 1},
        {text: 'Nein', value: 0},
      ]
    },
  },
  methods: {
    updateError: function () {
      this.questionError = this.$store.getters["qstore/getErrors"](this.getQuestionId);
    },
    actionBtn: function () {

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
    eventHandler: function (keyboardKey) {
      //this function is called when the number: 1 or 2 is pressed
      //it iterates over the choices of the radio btn and assign the value to
      //the variable 'selection' which controls the selected radio btn
      let choices = this.getChoices;
      if ('j' == keyboardKey) {
        this.selection = choices[0].value;
      }
      if ('n' == keyboardKey) {
        this.selection = choices[1].value;
      }

      //when the enter key is pressed must validate the question or go to the next page
      if (keyboardKey == 'Enter') {

        this.actionBtn();

      }
    },
  }
}
</script>

<style lang="scss" scoped>


</style>