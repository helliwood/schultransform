<template>
  <div class="col-lg-8 col-md-11 col-sm-11  m-auto">
    <category-icon class="q-icon-sm mb-1" :category-id="getCategoryId"></category-icon>
    <h4 class="q-color-primary pb-1">{{ getQuestionnaireData.name }}</h4>
    <h5 class="pb-2">{{ getQuestionnaireData.description }}</h5>
    <div>
      <ok-button :onclicked="okNextPage" :status-class="getClass"></ok-button>
    </div>
  </div>

</template>

<script>
import CategoryIcon from "../childcomponents/CategoryIcon";
import OkButton from "../Options/OkButton";

export default {
  components: {
    OkButton,
    CategoryIcon,
  },
  name: "Group",
  props: {
    questionnaireData: null,
    classAnimate: null,
  },
  mounted() {
    //keyboard event
    this.$store.getters["qstore/getEventBus"].$on('question_answer_iteration', this.eventHandler);
  },
  destroyed() {
    this.$store.getters["qstore/getEventBus"].$off('question_answer_iteration', this.eventHandler);
  },
  computed: {
//just to set the background of the ok btn to primary
    getClass() {
      return true;
    },
    getQuestionnaireData() {
      return this.questionnaireData;
    },
    getCategoryId() {
      return this.$store.getters["qstore/getCategoryId"];
    },

  },
  methods: {
    okNextPage: function () {
      this.$store.dispatch("qstore/updatePointer", {flag: 'next', pointer: 1})//First page of the questionnaire
    },
    eventHandler: function (keyboardKey) {
      //this function is called when the Enter key is pressed
      if (keyboardKey == 'Enter') {
        this.okNextPage();
      }

    },

  },
}
</script>

<style scoped>

</style>