<template>
  <div>
    <button type="button" class="btn text-white q-bg-primary pr-2 pl-2 q-send-btn mt-2" @click.once="onpressed"
            :class="getWasSaved">
      Speichern und Ergebnis ansehen
    </button>
  </div>
</template>

<script>
export default {
  name: "SendBtn",
  props: {
    onpressed: {type: Function},
  },
  computed: {

    //retrieve if the questionnaire was successfully saved: returns a css class to set the button sent background gray
    getWasSaved() {
      if (this.$store.getters["qstore/getWasSaved"]) {
        return 'q-gray-bg';
      } else {
        return '';
      }
    },

  }

}
</script>

<style lang="scss" scoped>
.questionnaire {
  .q-bg-primary.q-gray-bg {
    background: gray !important;
    opacity: .3;
  }
}


</style>