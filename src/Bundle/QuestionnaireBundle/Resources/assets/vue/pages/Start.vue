<template>
  <div class="col-12 col-sm-11 m-auto text-center">

    <div class="q-start-content-wrapper">
      <category-icon class="q-icon-start mb-2" :category-id="getCategoryId"></category-icon>
      <h4 class="pb-2 pb-sm-0" :class="!isQuestionnaireSchoolType?'pb-5 pb-sm-4':''">{{ getQuestionnaireData.name }}</h4>
      <h5 v-if="isQuestionnaireSchoolType" :class="getQuestionnaireData.schooltype? 'pb-0 mb-0': 'pb-3 pb-sm-2'">{{
          getQuestionnaireData.category.name
        }}</h5>
      <p v-if="isQuestionnaireSchoolType && getQuestionnaireData.schooltype"
         class="pt-0 pb-3 pb-sm-2 text-black-50 m-0">
        {{ getQuestionnaireData.schooltype.name }}</p>

      <!--Check if the user has this questionnaire already filled out-->
      <div v-if="isUserFilledOut" class="q-user mb-1">
        <p class="q-user-p">Sie haben diesen Fragebogen bereits <b>{{ isUserFilledOut.times }}</b> Mal ausgefüllt. Das
          letzte
          Mal am {{ isUserFilledOut.lastdate }}</p>
        <a class="btn btn-secondary q-user-a" v-if="getSchoolTypeAndDashboardUrl" :href="getSchoolTypeAndDashboardUrl">Meine
          Ergebnisse</a>
        <a v-else class="btn btn-secondary q-user-a" href="/PublicUser/user-success">Meine Ergebnisse</a>
      </div>

      <button class="btn q-bg-primary text-white px-4 q-start-btn mb-3" @click="startQuestionnaire">Start</button>
    </div>

  </div>

</template>

<script>
import CategoryIcon from "../childcomponents/CategoryIcon";

export default {
  components: {
    CategoryIcon,
  },
  name: "Start",
  props: {
    questionnaireData: null,
  },
  mounted() {
    //keyboard event
    this.$store.getters["qstore/getEventBus"].$on('question_answer_iteration', this.eventHandler);
  },
  destroyed() {
    this.$store.getters["qstore/getEventBus"].$off('question_answer_iteration', this.eventHandler);
  },
  data() {
    return {}
  },
  computed: {
    isQuestionnaireSchoolType() {
      let toReturn = false;
      if (this.questionnaireData && (this.questionnaireData.type)) {
        if (this.questionnaireData.type === 'school') {
          toReturn = true;
        }
      }
      return toReturn;
    },
    getSchoolTypeAndDashboardUrl() {
      let toReturn = false;
      if (this.questionnaireData && (this.questionnaireData.type)) {
        if (this.questionnaireData.type === 'school') {
          if (this.questionnaireData.category) {
            toReturn = '/Dashboard/Teacher/category-overview/' + this.questionnaireData.category.id;
          }
        }
      }
      return toReturn;
    },
    //check if the user already filed out this questionnaire
    isUserFilledOut() {
      if (this.questionnaireData.user && (this.questionnaireData.user.wasHere)
          && (this.questionnaireData.user.times)
          && (this.questionnaireData.user.lastdate)) {
        return {lastdate: this.questionnaireData.user.lastdate, times: this.questionnaireData.user.times};
      }
      return false;
    },

    getCategoryId() {
      return this.$store.getters["qstore/getCategoryId"];
    },
    getClassAnimate() {
      return this.classAnimate;
    },
    getQuestionnaireData() {
      return this.questionnaireData;
    },
  },
  methods: {
    startQuestionnaire: function () {
      this.$store.dispatch("qstore/updatePointer", {flag: 'next', pointer: 1})//First page of the questionnaire
    },
    eventHandler: function (keyboardKey) {
      //this function is called when the Enter key is pressed
      if (keyboardKey === 'Enter') {
        this.startQuestionnaire();
      }

    },
  }
}
</script>

<style lang="scss" scoped>
.q-start-content-wrapper {
  padding-top: 4%;

  .q-start-btn,
  .q-user-a {
    width: 10rem;
  }

  .q-user {
    p.q-user-p {
      font-size: .7em;
      line-height: 1.5em;

    }

    a {
      font-size: .8em;

      &:hover {
        // text-decoration: none;
        // border-color: #006292;
        //color: black;
      }
    }
  }

  h4 {
    font-size: 1.4em;
  }

  h5 {
    font-size: 1.1em;
  }
}
</style>