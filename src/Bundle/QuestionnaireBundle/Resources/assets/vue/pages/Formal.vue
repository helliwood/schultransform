<template>
  <div class="col-lg-8 col-md-11 col-sm-11  m-auto">
    <category-icon class="q-icon-sm mb-1" :category-id="getCategoryId"></category-icon>
    <component :is="formalTypes[questionnaireData.pageNumber]"></component>
  </div>
</template>

<script>
import FormalQuestion from "./childcomponents/FormalQuestion";
import FormalTitle from "./childcomponents/FormalTitle";
import CategoryIcon from "../childcomponents/CategoryIcon";

export default {
  components: {
    CategoryIcon,
  },
  name: "Formal",
  props: {
    questionnaireData: null,
  },
  data() {
    return {
      formalTypes: {
        '1': FormalTitle,
        '2': FormalQuestion,
      },
    }
  },
  computed: {
    getQuestionnaireData() {
      return this.questionnaireData;
    },
    getCategoryId() {
      return this.$store.getters["qstore/getCategoryId"];
    },

  },
}
</script>

<style scoped>

</style>