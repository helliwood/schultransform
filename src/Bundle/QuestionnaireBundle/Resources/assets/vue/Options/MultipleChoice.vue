<template>

  <div>
    <component :is="getComponent" :questionData="getQuestion"></component>

  </div>
</template>

<script>
import Multiple from "./Multiple";
import Single from "./Single";
import MultipleCustom from "./MultipleCustom";

export default {
  name: "MultipleChoice",
  props: {
    questionData: null,
  },
  data() {
    return {}
  },
  mounted() {

  },
  computed: {

    getQuestion() {
      return this.questionData;
    },

    //it selects the component based on the properties -> 'allow_multiple_selection' when false load -> Single: otherwise
    // ask if-> Multiple has 'allow_other_choice': false -> Multiple, true -> MultipleCustomChoice
    getComponent() {
      //return (this.getQuestion.properties.allow_multiple_selection) ? (Multiple) : Single;
      let _component = null;
            switch (this.getQuestion.properties.allow_multiple_selection) {
              case true:
                if (this.getQuestion.properties.allow_other_choice) {
                  _component = MultipleCustom;
                } else {
                  _component = Multiple;
                }
                break;
              case false:
                _component = Single;
                break;
              default:
                _component = Multiple;
            }
            return _component;

    },
    test() {
      return this.$store.getters["qstore/getData"].questionGroups[this.pointer.counterGroup].questions[this.pointer.counterQuestion].properties.allow_multiple_selection;
    },
    //it retrieves the number of steps coming from the question properties -> steps
    getQuestionProperties() {
      return this.$store.getters["qstore/getData"].questionGroups[this.pointer.counterGroup].questions[this.pointer.counterQuestion].properties;
    },
    //it retrieves the labels that will come on the bottom of the options
    getLabels() {
      //TODO Verify if all the time comes the same number of labels elements
      return this.$store.getters["qstore/getData"].questionGroups[this.pointer.counterGroup].questions[this.pointer.counterQuestion].properties.labels;
    },
    getPointer() {
      return this.pointer;
    }
  },
}
</script>

<style scoped>

</style>