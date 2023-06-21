<template>
  <div class="col-lg-8 col-md-11 col-sm-11 m-auto q-pos-relative">
<!--<h5>Question id:{{getQuestion.id}}</h5>-->
    <category-icon  class="q-icon-sm mb-1" :category-id="getCategoryId"></category-icon>
    <component :is="questionTypes[getQuestion.type]" :questionData="getQuestion"></component>
  </div>

</template>

<script>
import OpinionScale from "../Options/OpinionScale";
import MultipleChoice from "../Options/MultipleChoice";
import YesNo from "../Options/YesNo";
import LongText from "../Options/LongText";
import CategoryIcon from "../childcomponents/CategoryIcon";

export default {
  components: {
    OpinionScale,
    MultipleChoice,
    YesNo,
    LongText,
    CategoryIcon,
  },
  name: "Question",
  props: {
    questionnaireData: null,
    classAnimate:null,
  },
  data() {
    return {
      questionTypes: {
        'opinion_scale': OpinionScale,
        'multiple_choice': MultipleChoice,
        'yes_no': YesNo,
        'long_text': LongText,
      }
    }
  },
  computed: {
    getQuestion() {
      return this.questionnaireData.question;
    },
    getCategoryId() {
      return this.$store.getters["qstore/getCategoryId"];
    },

  },

}
</script>

<style lang="scss" scoped>
.dev{
 background: gray;
  color: white;
}
</style>