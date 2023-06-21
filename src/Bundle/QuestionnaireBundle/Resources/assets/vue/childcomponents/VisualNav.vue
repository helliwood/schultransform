<template>
  <div v-if="!isLoading" class="position-absolute row q-visual-nav ml-0">
    <!-- Differentiates if it is 'start' page -->
    <div v-if="getPageName === 'start'" class="q-bg-primary">
      <h2 class="q-title px-3">Fragebogen</h2>
    </div>
    <div v-else class="d-flex flex-row q-visual-nav-wrapper" :class="isSchoolBoard?'q-is-school-board':''">
      <h3 class="text-white text-center" :class="getCatPos === 1 ? 'q-bg-primary q-active':'q-no-active'">
        Selbsteinschätzung</h3>

      <h3 v-if="isSchool" class="text-white text-center"
          :class="getCatPos === 2 ? 'q-bg-primary q-active':'q-no-active'">
        Detailfragen</h3>

      <h3  v-if="isSchool"  class="text-white text-center" :class="getCatPos === 3 ? 'q-bg-primary q-active':'q-no-active'">Abschluss</h3>
      <h3  v-if="!isSchool"  class="text-white text-center" :class="getCatPos === 2 ? 'q-bg-primary q-active':'q-no-active'">Abschluss</h3>
    </div>
  </div>
</template>

<script>
export default {
  name: "VisualNav",
  props: {
    questionnaireData: null,
    pagesNames: null,
  },
  computed: {

    getPageName() {
      return this.pagesNames;
    },
    getUserType() {
      return this.$store.getters["qstore/getUserType"];
    },

    isSchool() {
      return this.getUserType === 'school';
    },

    isSchoolBoard() {
      return this.getUserType === 'school_board';
    },

    getCatPos() {
      if (this.questionnaireData) {
        return this.questionnaireData.catPosition;
      }
    },
    isLoading() {
      return this.$store.getters["qstore/isLoading"];
    },
  }


}
</script>

<style lang="scss" scoped>
@import "assets/scss/frontend";

.q-visual-nav {
  background: white;
  width: 100%;
  z-index: 100;

  h3 {
    background: #d4d3d3;
    background: radial-gradient(circle, #c0c0c0 0%, #c5c5c5 100%, #a2a2a2 100%);
    margin-right: 1px;
    color: $white;
    padding: .2em .8em;
    font-size: 1.3em;
  }

}


</style>