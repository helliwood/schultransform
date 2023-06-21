<template>
  <div>
    <h4 class="q-color-primary pb-1">{{ getQuestion.question }}</h4>
    <h5 class="mb-3">{{ getProperties.description }}</h5>

    <b-form-input id="q_free_option" ref="q_input_text" v-model="getSelected" :placeholder="getPlaceHolder" class="p-4 mb-3">
    </b-form-input>

    <ok-btn :onclicked="actionBtn" :status-class="hasWritten"></ok-btn>
    <error v-if="getQuestionError" :message="getMessage"></error>

  </div>

</template>

<script>
import OkButton from "./OkButton";
import Error from "./childcomponents/Error";
import {EventHelper} from "../Utils/EventHelper";

export default {
  components: {
    okBtn: OkButton,
    error: Error,
  },
  name: "LongText",
  props: {
    questionData: null,
  },
  data() {
    return {
      questionError: null,
    }
  },
  mounted() {
    //keyboard event
    this.$store.getters["qstore/getEventBus"].$on('question_answer_iteration', this.eventHandler);

    //time of duration of the entry animation

  },
  destroyed() {
    this.$store.getters["qstore/getEventBus"].$off('question_answer_iteration', this.eventHandler);
  },
  computed: {
    //get the message to display when a error comes
    getMessage() {
      return 'Bitte füllen Sie das Eingabefeld aus';
    },

    //verify if there is a selected value in order to give this flag to the ok btn to change the color
    hasWritten() {
      return typeof this.getSelected === 'string' && this.getSelected !== '';
    },
    //retrieve the status of the property initializeValidation from the store
    startedInitializeValidation() {
      return this.$store.getters["qstore/getInitializeValidation"];
    },
    getPlaceHolder() {
      return 'Geben Sie hier Ihre Überlegungen ein.'
    },
    getSelected: {
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
        this.$store.commit("qstore/setQuestionnaireData", answer);
        //retrieves the current state of the state.error variable
        this.updateError();
      },
    },
    getProperties() {
      return this.getQuestion.properties;
    },
    getQuestionType() {
      return this.getQuestion.type;
    },
    getQuestion() {
      return this.questionData;
    },
    getQuestionId() {
      return this.getQuestion.id;
    },
    getResults() {
      return this.$store.getters["qstore/getResult"]
    },
    getQuestionError() {
      this.updateError();
      return this.questionError;
    },
    //verify if the question has errors
    hasErrorsThisQuestion() {
      this.questionError = this.$store.getters["qstore/getErrors"](this.getQuestionId);
    },
  },
  methods: {
    focusInput() {
      this.$refs.q_input_text.focus();
    },

    updateError: function () {
      this.questionError = this.$store.getters["qstore/getErrors"](this.getQuestionId);
    },

    actionBtn: function () {
      //Validates the question
      //differentiates if the initializeValidation was started: for have another behaviour-> is going to jump to the next error
      this.$store.dispatch("qstore/validateCurrentQuestion", this.getQuestionId);
      //update the status of the error for this question
      this.updateError();

      //differentiates if the initializeValidation was started: for have another behaviour-> is going to jump to the next error
      if (this.startedInitializeValidation) {
        //go to the next question with error
        //validate all again
        this.$store.dispatch("qstore/validateQuestionnaire");

      } else {
        //if no error go to the nextvalidation page
        if (!this.getQuestionError) {
          this.$store.dispatch("qstore/updatePointer", {flag: 'next'})
        }
      }


    },

    eventHandler: function (keyboardKey) {
      //this function is called when the Enter key is pressed
      if (keyboardKey == 'Enter') {
        this.actionBtn();
      }

    },
  }
}
</script>

<style scoped>

</style>