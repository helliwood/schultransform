<template>
  <div>
    <h4 class="q-color-primary pb-3">{{ getTitle }}</h4>
    <button class="btn text-white q-bg-primary q-weiter-btn" @click="nextPage">Weiter</button>
    <p class="q-color-primary pl-2">Drücken Sie Enter &#9166;</p>
  </div>
</template>

<script>
export default {
  name: "FormalTitle",
  mounted() {
    //keyboard event
    this.$store.getters["qstore/getEventBus"].$on('question_answer_iteration', this.eventHandler);
  },
  destroyed() {
    this.$store.getters["qstore/getEventBus"].$off('question_answer_iteration', this.eventHandler);
  },
  computed: {
    getTitle() {
      return 'Jetzt haben Sie es gleich geschafft. Nur noch das Formale.';
    },
  },

  methods: {
    nextPage: function () {
      this.$store.dispatch("qstore/updatePointer", {flag: 'next', pointer: 1})//Next page of the questionnaire
    },
    eventHandler: function (keyboardKey) {
      //this function is called when the Enter key is pressed
      if (keyboardKey === 'Enter') {
        this.nextPage();
      }

    },
  }

}
</script>

<style scoped>
p{
  font-size: .8em;
  display: inline-block;
}
</style>