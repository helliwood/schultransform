<template>
  <div>
    <h4 class="q-color-primary pb-2">{{ getText }}</h4>
    <b-form-group v-slot="{ ariaDescribedby }">
      <b-form-radio-group
          buttons
          id="radio-group-1"
          v-model="getSelected"
          :aria-describedby="ariaDescribedby"
          name="radio-options"
          class="q-bg-primary-rgba-2 q-rating"
      >

        <b-form-radio :value="1" :class="getValueOfSelected > 1?'q-icon-evaluation-active':''"><i class="fad fa-heart q-heart"></i></b-form-radio>
        <b-form-radio :value="2" :class="getValueOfSelected > 2?'q-icon-evaluation-active':''"><i class="fad fa-heart q-heart"></i></b-form-radio>
        <b-form-radio :value="3" :class="getValueOfSelected > 3?'q-icon-evaluation-active':''"><i class="fad fa-heart q-heart"></i></b-form-radio>
        <b-form-radio :value="4" :class="getValueOfSelected > 4?'q-icon-evaluation-active':''"><i class="fad fa-heart q-heart"></i></b-form-radio>
        <b-form-radio :value="5"><i class="fad fa-heart q-heart"></i></b-form-radio>
      </b-form-radio-group>

    </b-form-group>


  </div>
</template>

<script>
export default {
  name: "Evaluation",
  data() {
    return {
      selection: null,
    }
  },
  mounted() {
    this.$store.getters["qstore/getEventBus"].$on('question_answer_number', this.eventHandler);
  },
  destroyed() {
    this.$store.getters["qstore/getEventBus"].$off('question_answer_number', this.eventHandler);
  },
  computed: {
    //get value of the selected radio btn
    getValueOfSelected(){

      return this.getSelected;

    },

    getResults() {
      return this.$store.getters["qstore/getResult"]
    },
    //where does this text needs to come from -> DB?
    getText() {
      return 'Wie hat Ihnen der Fragebogen gefallen?';
    },
    getChoices() {
      return [
        {text: '', value: 1},
        {text: '', value: 2},
        {text: '', value: 3},
        {text: '', value: 4},
        {text: '', value: 5},
      ];
    },
    getSelected: {
      get() {
        //verify if is there a answer on the store saved before: if so return
        // the stored value otherwise -> return an empty array
        if (this.getResults.evaluation) {
          return this.getResults.evaluation;
        } else {
          return [];
        }

      },
      set(value) {
        //save the evaluation of the questionnaire on the store
        this.$store.commit("qstore/setEvaluation", value);
      },
    },
  },
  methods: {
    eventHandler: function (keyboardKey) {
      //this function is called when the number: 1 or 2 is pressed
      //it iterates over the choices of the radio btn and assign the value to
      //the variable 'selection' which controls the selected radio btn
      let choices = this.getChoices;
      choices.forEach((value, index) => {
        if (index + 1 == keyboardKey) {
          this.getSelected = value.value;
        }
      });
    },
  },
}
</script>

<style lang="scss" scoped>
@import "assets/scss/frontend";
div#radio-group-1 {
  border: rgba(100,100,100,.5) solid thin;

 i{
   font-size: 3em;
 }
  label.btn.btn-secondary {
    border: none !important;
    background: transparent;
  }


}

</style>