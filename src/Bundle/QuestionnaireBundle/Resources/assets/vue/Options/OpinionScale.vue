<template>
  <div>
    <h4 class="q-color-primary pb-3">{{ questionData.question }}</h4>
    <!--labels for the mobil view port-->
    <div class="d-flex flex-column">
      <p class="q-labels-mobil q-color-primary">{{getLabelOne}} <span>&rArr;</span> {{ getLabels.left }}</p>
      <p class="q-labels-mobil q-color-primary">{{getSteps | getMiddle}} <span>&rArr;</span> {{ getLabels.center }}</p>
      <p class="q-labels-mobil q-color-primary pb-2">{{getLabelLast}} <span>&rArr;</span> {{ getLabels.right }}</p>
    </div>


    <!-- iteration on inputs based on the number of steps -->
    <b-form-group v-slot="{ ariaDescribedby }">
      <b-form-radio-group
          buttons
          id="radio-group-1"
          v-model="selection"
          :options="getChoicesRBtns"
          :aria-describedby="ariaDescribedby"
          name="radio-options"
          class="d-flex justify-content-between q-radio-opinion"
      ></b-form-radio-group>
    </b-form-group>

    <div class="d-flex justify-content-between">
      <p class="q-labels q-color-primary">{{ getLabels.left }}</p>
      <p class="q-labels q-color-primary">{{ getLabels.center }}</p>
      <p class="q-labels q-color-primary">{{ getLabels.right }}</p>
    </div>

    <ok-btn :onclicked="actionBtn" :status-class="hasSelectedAQuestion"></ok-btn>
    <error v-if="getQuestionError"></error>

  </div>
</template>

<script>
import OkButton from "./OkButton";
import Error from "./childcomponents/Error";

export default {
  name: "OpinionScale",
  props: {
    questionData: null,
  },
  data() {
    return {
      questionError: null,
    }
  },
  components: {
    okBtn: OkButton,
    error: Error,
  },
  mounted() {
    //register the keyboard events
    this.$store.getters["qstore/getEventBus"].$on('question_answer_number', this.eventHandler);
    this.$store.getters["qstore/getEventBus"].$on('question_answer_iteration', this.eventHandler);
  },
  destroyed() {
    //unregister the keyboard events
    this.$store.getters["qstore/getEventBus"].$off('question_answer_number', this.eventHandler);
    this.$store.getters["qstore/getEventBus"].$off('question_answer_iteration', this.eventHandler);
  },
  beforeUpdate() {
  },
  filters: {

    getMiddle: function (value) {
      if (!value) return '';
      return Math.floor(value / 2);
    },
    getStart: function (value) {
      return this.getStartAtOne ? 1 : 0;
    },

  },
  methods: {
    eventHandler: function (keyboardKey) {
      //this function is called when the number: 1 or 2 is pressed
      //it iterates over the choices of the radio btn and assign the value to
      //the variable 'selection' which controls the selected radio btn
      let choices = this.setChoices();
      choices.forEach((value, index) => {
        if (index == keyboardKey) {
          this.selection = value.value;
        }
      });

      //when the enter key is pressed must validate the question or go to the next page
      if (keyboardKey == 'Enter') {

        this.actionBtn();

      }


    },
    setChoices: function () {
      let arrayReturn = [];
      //it retrieves the number of steps and create an object containing this number of elements
      //and verifies if it starts at one
      let addNumber = 0;
      if (this.getStartAtOne) {
        addNumber = 1;
      }
      for (let i = 0; i < this.getSteps; i++) {
        let displayedText = i + addNumber;
        arrayReturn.push(
            {text: displayedText, value: i, checked: false,}
        );
      }
      return arrayReturn;
    },

    //validation the ok btn
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
    updateError: function () {
      this.questionError = this.$store.getters["qstore/getErrors"](this.getQuestionId);
    },

  },

  computed: {
    //retrieve the status of the property initializeValidation from the store
    startedInitializeValidation() {
      return this.$store.getters["qstore/getInitializeValidation"];
    },
    //it retrieves the values of the labels for the mobil viewport
    getLabelOne() {
      //verify if start by zero
      if (this.getStartAtOne) {

        return 1;

      }
      return 0;

    },
    getLabelLast(){
      //verify if start by zero
      if (this.getStartAtOne) {

        return this.getSteps;

      }
      return this.getSteps-1;


    },

    //verify if there is a selected value in order to give this flag to the ok btn to change the color
    hasSelectedAQuestion() {
      return typeof this.selection === 'number';
    },
    getQuestionError() {
      this.updateError();
      return this.questionError;
    },

    //verify if the final validation was started: Senden Btn was pressed
    getInitializeValidation() {
      return this.$store.getters["qstore/getInitializeValidation"];
    },
    //this computed retrieves and save the data in/on the store
    selection: {
      get() {
        //it assigns the value stored on the store if is available to the radio btn
        if (this.getResults.questions[this.getQuestionId]) {
          return this.getResults.questions[this.getQuestionId].value;
        } else {
          return [];
        }
      },
      //save the value in the store
      set(value) {
        this.testClass = null;
        let answer = {
          id: this.getQuestionId,
          answer: {
            questionId: this.getQuestionId,
            // questionType: this.getQuestionType,
            value: value
          }
        }

        this.$store.commit("qstore/setQuestionnaireData", answer);
        //retrieves the current state of the state.error variable
        this.updateError();
      }
    },
    //verify if the question has errors
    hasErrorsThisQuestion() {
      return this.$store.getters["qstore/getErrors"](this.getQuestionId);
    },
    getQuestionType() {
      return this.getQuestion.type;
    },
    getQuestion() {
      return this.questionData;
    },
    getChoicesRBtns() {
      return this.setChoices();
    },
    getRequired() {
      return this.getQuestion.required;
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
      return this.getQuestion.properties.labels;
    },
    getQuestionId() {
      return this.getQuestion.id;
    },
    getResults() {
      return this.$store.getters["qstore/getResult"]
    },
  },
}
</script>

<style lang="scss" scoped>
@import "assets/scss/frontend";
/*********Styling begin***********/

.q-labels {
  font-size: .8em;
}


/*Test*/


/*DEV*/
.dev-tool-window_ {
  position: fixed;
  top: 0;
  left: 0;
  width: 20%;
  background: rgba(200, 200, 200, .5);
}

</style>