<template>
  <div>
    <h4 class="q-color-primary pb-2">{{ getTitle }}</h4>
    <b-form-group v-slot="{ ariaDescribedby }">
      <b-form-checkbox-group
          id="checkbox-group-1"
          v-model="selection"
          :options="getChoices"
          :aria-describedby="ariaDescribedby"
          name="multiple_question"
          class="q-formal-question"
      ></b-form-checkbox-group>
    </b-form-group>
    <ok-btn :onclicked="actionBtn" :status-class="hasSelectedAQuestion"></ok-btn>
    <error v-if="getQuestionError"></error>
  </div>
</template>

<script>
import OkButton from "../../Options/OkButton";
import {QuestionnaireHelper} from "../../Utils/QuestionnaireHelper";
import Error from "../../Options/childcomponents/Error";

export default {
  components: {
    okBtn: OkButton,
    error: Error,
  },
  name: "FormalQuestion",
  data() {
    return {
      helper: null,
      test: []
    }
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
    //verify if there is a selected value in order to give this flag to the ok btn to change the color
    hasSelectedAQuestion() {
      return this.selection.length > 0;
    },
    //it retrieves if this question has an error
    getQuestionError() {
      this.updateError();
      return this.questionError;
    },

    selection: {
      get() {
        if (this.getResults.formal) {
          return this.getResults.formal;
        } else {
          return [];
        }
      },
      set(value) {
        //it is use to save the selection in the Store
        //TODO save only one value
        this.$store.commit("qstore/setFormal", value);
      }
    },

    getUserType() {
      return this.$store.getters["qstore/getUserType"];
    },

    getResults() {
      return this.$store.getters["qstore/getResult"];
    },
    getTitle() {
      let title_old = 'Schultransform wird in Zukunft eine Verknüpfung zwischen Schulen und Schulträgern ermöglichen. Sind Sie einverstanden, dass ihre Notizen anonym geteilt werden?';
      let title = 'Schultransform bietet eine Verknüpfung zu Schulen und Schulträgern. Ihre Angaben in der offenen Frage können unter Umständen Rückschlüsse auf Personen ermöglichen.';

      return title;
    },
    getChoices() {

      let toReturn = [];
      if (this.getUserType === 'school_board') {
        toReturn = [
          {
            text: 'Ich bin einverstanden, dass meine Notizen anonym mit meinen Schulen geteilt werden, sobald diese Funktion im Selbstcheck-Werkzeug verfügbar ist.',
            value: 1
          },

          {
            text: 'Ich bin einverstanden, dass meine Notizen anonym mit dem Entwicklungsteam von Schultransform zur Evaluation und Weiterentwicklung geteilt werden.',
            value: 2
          },
        ];

      } else {

        toReturn = [
          {
            // text: 'Ich bin einverstanden, dass meine Notizen anonym mit unserem Schulträger geteilt werden, sobald diese Funktion im Selbstcheck-Werkzeug verfügbar ist.',
            text: 'Ich bin einverstanden, dass meine Angaben in der offenen Frage zur Auswertung an meine Schule, den Schulträger und das Entwicklungsteam von Schultransform weitergegeben werden können.',
            value: 1
          },
          //
          // {
          //   text: 'Ich bin einverstanden, dass meine Notizen anonym mit dem Entwicklungsteam von Schultransform zur Evaluation und Weiterentwicklung geteilt werden.',
          //   value: 2
          // },
        ];

      }

      return toReturn;

    },
  },

  methods: {
    //it updates the error
    updateError: function () {
      this.questionError = this.$store.getters["qstore/getErrors"](this.getQuestionId);
    },
    actionBtn: function () {
      this.$store.dispatch("qstore/updatePointer", {flag: 'next'});
    },
    eventHandler: function (keyboardKey) {
      //assign a letter to each input in order to be able to set a keyboard event to the input
      //match table from the QuestionnaireHelper
      let letters = this.$data.helper.getLetters();

      let choices = this.getChoices;
      choices.forEach((value, index) => {
        if (letters[index] == keyboardKey) {
          //uncheck the checkbox if already was selected
          if (!this.selection.includes(value.value)) {
            this.selection.push(value.value);
          } else {
            this.selection = this.$data.helper.removeItemInArrayByValue(this.selection, value.value);
          }

        }
      });
      //this function is called when the Enter key is pressed
      if (keyboardKey == 'Enter') {
        this.actionBtn();
      }
    },
  },
}
</script>

<style lang="scss" scoped>


</style>