<template>
  <div class="q-other-choice-wrapper">
    <b-form-group v-slot="{ ariaDescribedby }">
      <b-form-checkbox-group
          id="q-reason"
          v-model="other_reason_checkbox"
          :aria-describedby="ariaDescribedby"
          name="other-choice"
          :class="getActiveClass"
      >
        <b-form-checkbox :value="-2"><span id="q-span-reason">{{ getTextOtherReason }}</span></b-form-checkbox>

      </b-form-checkbox-group>

    </b-form-group>
    <b-form-input :class="getInputClass" id="q_other_reason" ref="q_other_reason_ref"
                  type="text"
                  v-model="other_reason_input"
                  placeholder="Begründung eingeben">
    </b-form-input>
  </div>
</template>
<script>
export default {
  name: "CompReasonNoAnswer",
  props: {
    questionId: null,
  },
  data() {
    return {
      activeClassOther: null,
      labelText: 'Zu dieser Fragestellung liegen mir aktuell keine Informationen vor.',
      inputTextClass: 'q-hide',
    }
  },
  watch: {
    other_reason_checkbox: function (new_value, old_value) {
      //empty the selection array
      if (new_value.length > 0) {
        // this.showInput();
      }


    },
    other_reason_input: function (new_value, old_value) {

    },

  },
  computed: {
    getQuestionId() {
      return this.questionId;
    },

    //assign a active class to the element for styling purposes
    getActiveClass() {
      return this.activeClassOther;
    },

    getResults() {
      return this.$store.getters["qstore/getResult"]
    },
    //other choice
    other_reason_checkbox: {
      get() {
        //retrieve the value if was saved on the store
        if (this.getResults.questions[this.getQuestionId]) {
          //check if the only value is a 'null' string
          //and it is the only value in the array
          if (this.getResults.questions[this.getQuestionId].value.includes('null')) {
            this.$parent.$data.propDisabled = true;
            //flag to cancel the keyboard events
            this.$parent.$data.classFlag = true;
            //show input
            this.showInput();
            return [-2];
          } else {
            return [];
          }
        } else {
          return [];
        }
      },
      set(value) {
        if (value.includes(-2)) {
          this.actionsIfChecked();
          //save the value 'null' on the store
          let answer = {};
          answer = {
            id: this.getQuestionId,
            answer: {
              questionId: this.getQuestionId,
              //questionType: this.getQuestionType,
              value: ['null'],
            }
          }

          this.$store.commit("qstore/setQuestionnaireData", answer);


          this.showInput();
        } else {
          //empty the array to aovid conflicts with the validation
          this.$parent.selection = [];

          this.$parent.$data.propDisabled = false;

          this.hideInput();
          //flag to cancel the keyboard events
          this.$parent.$data.classFlag = false;
        }
      }

    },

    other_reason_input: {
      get() {

        //retrieve the value if was saved on the store
        if (this.getResults.questions[this.getQuestionId]) {
          //check if the only value is a 'null' string
          //and it is the only value in the array
          if (this.getResults.questions[this.getQuestionId].value.includes('null')) {
            //check if value available for the input
            if (this.getResults.questions[this.getQuestionId].reason) {
              return this.getResults.questions[this.getQuestionId].reason;
            }
          }
        }

      },
      set(value) {
        let answer = {};
        answer = {
          id: this.getQuestionId,
          answer: {
            questionId: this.getQuestionId,
            //questionType: this.getQuestionType,
            value: ['null'],
            reason: value,
          }
        }

        this.$store.commit("qstore/setQuestionnaireData", answer);

      }

    },
    //class to handle the input text
    getInputClass() {
      return this.inputTextClass;
    },
    //it retrieves the text for the checkbox other choice
    getTextOtherReason() {
      return this.labelText;
    },


  },
  methods: {
    //action to be perform when the checkbox is selected
    actionsIfChecked: function () {
      //empty the selection array from parent
      //disable the checkbox to avoid the user select other and
      //be focused on the input in order to give a reason to
      // no answer the question
      this.$parent.selection = [];
      this.$parent.$data.propDisabled = true;
      //flag to cancel the keyboard events
      this.$parent.$data.classFlag = true;
    },

    //pack the focus and the input class
    showInput: function () {
      this.inputTextClass = 'q-show';
      this.labelText = '';
      this.activeClassOther = 'q-active-other';
      this.$parent.$data.classDisabled = 'q-disabled-checkbox';
      //  this.$refs['q_other_reason_ref'].focus();
    },
    hideInput: function () {
      this.labelText = 'Zu dieser Fragestellung liegen mir aktuell keine Informationen vor.'
      this.inputTextClass = 'q-hide';
      this.activeClassOther = '';
      this.$parent.$data.classDisabled = '';
    },
  },
}
</script>

<style lang="scss" scoped>

.questionnaire {

  #q-span-reason{
    display: block;
    padding-left: 16px;
    font-size: .9em;
  }
  .q-other-choice-wrapper {
    position: relative;

    /*Other choice*/
    div#q-reason {
      padding: .3em;

      > div {
        width: 100%;

        label {
          width: 100%;
        }
      }
    }


    input#q_other_reason {
      position: absolute;
      top: 8px;
      left: 46px;
      width: 91%;
      border: none;
      z-index: 100;
      height: 27px;
      padding: 0;
    }

    .q-hide {
      display: none;
    }

    .q-show {
      display: block;
    }

  }
}


</style>