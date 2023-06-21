<template>
  <div class="position-absolute q-nav-btns">
    <button type="button"
            class="btn q-bg-primary text-white pr-3 pl-3 text-center justify-content-center align-items-center"
            @click="backPointer()" :disabled="backBtnDeactivate"><i class="fad fa-chevron-up"></i></button>
    <button type="button" class="btn q-bg-primary text-white pr-3 pl-3 text-center" @click="forwardPointer()"
            :disabled="nextBtnDeactivate"><i class="fad fa-chevron-down"></i></button>
  </div>
</template>

<script>
export default {
  name: "NavBtns",
  mounted() {
    //keyboard event
    this.$store.getters["qstore/getEventBus"].$on('question_answer_iteration', this.eventHandler);
  },
  destroyed() {
    //keyboard event
    this.$store.getters["qstore/getEventBus"].$off('question_answer_iteration', this.eventHandler);
  },
  computed: {

    backBtnDeactivate() {
      let pointer = this.$store.getters["qstore/getPointer"];
      //before pointer 3: there are inter page, start page and inter page has a
      //mechanism that pass to the either next or back page.
      return pointer < 3;
    },
    nextBtnDeactivate() {
      let lastPage = this.$store.getters["qstore/getLengthMatchList"];
      let pointer = this.$store.getters["qstore/getPointer"];
      return pointer >= lastPage - 1;
    }
  },
  methods: {
    eventHandler: function (keyboardKey) {

      //this function is called when the leftArrow or rightArrow are pressed
      //'ArrowRight','ArrowLeft','ArrowUp','ArrowDown'
      if (keyboardKey == 'ArrowDown') {
        //do not fire if the page is the 'final' page(end of questionnaire)
        if (!this.nextBtnDeactivate) {
          this.forwardPointer();
        }
      } else if (keyboardKey == 'ArrowUp') {
        //do not fire if the page before is the start page(starting page)
        if (!this.backBtnDeactivate) {
          this.backPointer();
        }

      }

    },
    //TODO the logic must in the store going
    backPointer: function () {
      //it is triggered when the back btn is pressed
      // contains the logic ta navigate back in the questionnaire
      //this.updatePointer('back');
      this.$store.dispatch("qstore/updatePointer", {flag: 'back', pointer: null})
    },
    forwardPointer: function () {
      this.$store.dispatch("qstore/updatePointer", {flag: 'next', pointer: null})

      //it is triggered when the next btn is pressed
      // contains the logic ta navigate forward in the questionnaire

    },
  },
}
</script>

<style lang="scss" scoped>
/*Navigation Btns*/
.q-nav-btns {
  left: unset;
  right: 1em;
  bottom: 20px;
  z-index: 2000;

  button {
    padding: 4px 0px;
    margin-left: -4px;
    i {
      color: white;
      font-size: 23px;
    }
  }
}
</style>