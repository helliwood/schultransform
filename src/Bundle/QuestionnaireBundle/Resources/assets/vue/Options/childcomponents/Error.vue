<template>
  <div class="q-error-alert">
    <p>{{ getMessage }}</p>
  </div>
</template>

<script>
export default {
  name: "Error",
  props: {
    message: null,
    errorMsg: null,
  },
  computed: {
    //controls the error msg
    getMsg() {

      if (this.getResult.questions[this.getCurrentQuestionId]) {
        let questionR = this.getResult.questions[this.getCurrentQuestionId];

        if (questionR.value.includes('null') && (!questionR.reason)) {
          return 'Bitte eine Begründung schreiben.';
        }
        if (questionR.value.includes(-1) && (!questionR.otherChoice)) {
          return 'Bitte formulieren Sie eine Option.';
        }
      } else {
        //is not saved: user did not selected any yet
        return 'Bitte wählen Sie eine Option.';
      }
    },

    getResult() {
      return this.$store.getters["qstore/getResult"];

    },
    //question id
    getCurrentQuestionId() {
      return this.$store.getters["qstore/getCurrentQuestion"];
    },

    getMessage() {
      return this.message ? this.message : 'Bitte wählen Sie eine Option';
    },
  },
}
</script>

<style lang="scss" scoped>
.q-error-alert {
  padding-top: 2px;

  p {
    color: red;
    font-size: .8em;
    position: absolute;
    bottom: -24px;
  }
}
</style>