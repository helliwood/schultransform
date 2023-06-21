<template>

  <div ref="q_scroll_if_mobil" class="row questionnaire no-gutters"
       :class="!isLoading?getCssCatClass+ '-shadow '+ getClassGrow:''">
    <!--Saving spinner-->
    <div class="position-absolute d-flex justify-content-center align-items-center q-spinner-saving" v-if="isSaving">
      <div class="spinner-border q-spinner" role="status">
        <span class="sr-only">Saving...</span>
      </div>
    </div>
    <!--Loading spinner-->
    <div class="d-flex justify-content-center m-auto" v-if="isLoading">
      <div class="spinner-border q-spinner" role="status">
        <span class="sr-only">Loading...</span>
      </div>
    </div>
    <div v-if="!isLoading && !isSaving" class="col-12" v-bind:class="getCssCatClass">

      <!--do not show the top div if it is the first(presentation) page-->
      <visual-nav :pages-names="getQuestionnaireData[0]" :questionnaireData="getQuestionnaireData[2]"></visual-nav>

      <!--DEVELOPMENT ONLY DELETE!!-->
      <div v-if="debugging">
        <div class="position-fixed" style="right: 0;top: 10px; z-index: 100000;">
          <b-button class="q-opacity-2" v-b-toggle.my-sidebar-a>R</b-button>
          <b-button class="q-opacity-2" v-b-toggle.my-sidebar-b>E</b-button>
        </div>
        <b-collapse class="position-fixed" style="
    right: 50%;
    left: 1%;
    top: 6%;
    width: 20%;
    height: 600px;
    overflow: scroll;
    background: rgba(235, 250, 235,0.5);
    z-index: 100;
    font-size: 12px;" id="my-sidebar-a" title="Questions results:" shadow>
          <div class="px-3 py-2">
          <pre>
        Saved questions{{ getResult }}
          </pre>
          </div>
        </b-collapse>
        <b-collapse class="position-fixed"
                    style="top: 6% ;background: #f0eaea; padding: 20px; z-index: 1000; right: 0"
                    id="my-sidebar-b" title="Question errors:" shadow>
          <div class="px-3 py-2">
          <pre>
            Errors{{
              getPropertyError
            }}
          </pre>
          </div>
        </b-collapse>
      </div>
      <!--   DEVELOPMENT ONLY DELETE!! END-->

      <div class="q-content-wrapper">
        <component :is="pagesTypes[getQuestionnaireData[0]]" :questionnaireData="getQuestionnaireData[1]"
                   :class="getClassAnimate" :id="'q-'+getPageName+'-page'"></component>
        <div v-if="getQuestionnaireData[0] !== 'start'" class="row">
          <!--do not show the back and prev btns if it is the first(presentation) page-->
          <nav-btns></nav-btns>
          <navigation :questionnaireData="getQuestionnaireData[2]"></navigation>
        </div>
      </div>
    </div>
  </div>

</template>

<script>

import store from "./store/store";
import Navigation from "./Utils/Navigation";
import Start from "./pages/Start";
import Group from "./pages/Group";
import Question from "./pages/Question";
import Final from "./pages/Final";
import {EventHelper} from "./Utils/EventHelper";
import NavBtns from "./childcomponents/NavBtns";
import Formal from "./pages/Formal";
import CategoryIcon from "./childcomponents/CategoryIcon";
import InterPage from "./pages/InterPage";
import VisualNav from "./childcomponents/VisualNav";

export default {
  components: {
    CategoryIcon,
    Navigation,
    NavBtns,
    VisualNav,
  },
  name: "Questionnaire",
  props: {
    questionnaireId: null,
    store,

  },
  data() {
    return {
      eventHelper: null,
      pointer: 0,
      counterGroup: 0,
      counterQuestion: 0,
      matchArray: [],
      pagesTypes: {
        'start': Start,
        'inter_page': InterPage,
        'group': Group,
        'question': Question,
        'formal': Formal,
        'final': Final,
      },
    }
  },

  created() {
    //create the 'qstore' if was not created
    if (!this.$store.hasModule("qstore")) {
      this.$store.registerModule("qstore", store);
    }
    //get the Vue instance from the store containing the keyboard events
    this.$data.eventHelper = new EventHelper(this.$store.getters["qstore/getEventBus"]);
  },
  mounted() {
    this.$store.dispatch("qstore/loadQuestionnaire", this.questionnaireId);
  },
  updated() {

    //save only if not in array
    //retrieve the pagesVisited var from store
    if (!this.getPagesVisited.includes(this.getPointer)) {
      this.$store.dispatch("qstore/insertVisitedPage", this.getPointer)
    }
    //setting the pointer in order to be watched
    this.pointer = this.getPointer;


  },
  watch: {
    pointer: function (new_value, old_value) {
      if (new_value !== old_value) {
        //scroll to the questionnaire
        //only in when the screen is smaller than 411
        if (screen.width <= 410 && Object.keys(this.getPropertyError).length === 0
            && this.getPropertyError.constructor === Object) {
          this.scrollToQuestionnaire();
        }
      }
    },
  },

  computed: {
    //retrieves the url param and show the btn s for development
    debugging() {
      try {
        let queryString = window.location.search;
        let urlParams = new URLSearchParams(queryString);
        if (urlParams.get('debugging') && (urlParams.get('debugging') === 'true')) {
          return true;
        }
      } catch (err) {
      }
      return false;
    },

    //retrieve the classGrow
    getClassGrow() {
      //check if the question has the prop classCss and set it on the store
      if (this.getQuestionnaireData[0] === 'question'
          && typeof this.getQuestionnaireData[1] === 'object'
          && this.getQuestionnaireData[1].question
          && (this.getQuestionnaireData[1].question.type === 'multiple_choice'
              || this.getQuestionnaireData[1].question.type === 'opinion_scale'
              && (this.getQuestionnaireData[1].question.classCssDesktop
                  || this.getQuestionnaireData[1].question.classCssMobil))) {
        let toReturn = '';

        if (this.getQuestionnaireData[1].question.classCssDesktop) {
          toReturn += this.getQuestionnaireData[1].question.classCssDesktop + ' '
        }
        if (this.getQuestionnaireData[1].question.classCssMobil) {
          toReturn += this.getQuestionnaireData[1].question.classCssMobil
        }
        return toReturn + ' q-has-grow';
      } else {
        //returns the shrink class
        if (this.getQuestionnaireData[0] === 'formal') {
          //find out the page number
          if (typeof this.getQuestionnaireData[1] === 'object' &&
              (this.getQuestionnaireData[1].hasOwnProperty('pageNumber'))) {
            if (this.getQuestionnaireData[1].pageNumber != 1) {
              return '';
            }

          }

        }
        return this.getQuestionnaireData[0] + '-q-shrink';
      }

    },
    //it retrieves the name of the page
    getPageName() {
      return this.getQuestionnaireData[0];
    },
    getPointer() {
      return this.$store.getters["qstore/getPointer"];
    },
    getPagesVisited() {
      return this.$store.getters["qstore/getPagesVisited"];
    },
    //it retrieves the state of the saving process: saving variable in the store
    isSaving() {
      return this.$store.getters["qstore/getIsSaving"];
    },
    //in order to update the value wasSaved from the store it need to be called from this component
    wasSaved() {
      return this.$store.getters["qstore/getWasSaved"];
    },
    //get the class for the animation
    getClassAnimate() {
      return this.$store.getters["qstore/getClassAnimate"];
    },
    //get error property from store, that will be validate at the end of the questionnaire
    getErrorToDisplay() {
      return this.$store.getters["qstore/getErrorToDisplay"];
    },
    //get the category of the questionnaire in order to set a class for this category
    //-> to help the css styling(color)
    getCssCatClass() {
      return this.$store.getters["qstore/getCategoryClass"]
    },
    //get the matchLIst with contains the component name
    getMatchList() {
      return this.$store.getters["qstore/getMatchList"];
    },
    //it retrieves the question and the page type('Start', 'Group', 'Question' or 'Final')
    getQuestionnaireData() {
      return this.$store.getters["qstore/getDataToShow"];
    },
    //current question Id
    getQuestionId() {
      return this.getQuestion.id;
    },
    //current question
    getQuestion() {
      return this.$store.getters["qstore/getQuestion"];
    },
    isLoading() {
      return this.$store.getters["qstore/isLoading"];
    },
    //retrieve the property error from the store
    getPropertyError() {
      return this.$store.getters["qstore/getPropertyError"];
    },
    getResult() {
      return this.$store.getters["qstore/getResult"];
    },

  },
  methods: {
    scrollToQuestionnaire() {
      let element = this.$refs.q_scroll_if_mobil;
      let topElem = element.offsetTop;
      window.scrollTo(0, topElem + 190);
    },

  }
}
</script>

<style lang="scss">
@import "assets/scss/frontend";

/*STYLING BEGINS*/

/**shadow**/
@each $key, $color in $category-colors {

  .questionnaire.q-category-#{$key}-shadow {
    -webkit-box-shadow: 19px 19px 0px 1px $color;
    box-shadow: 19px 19px 0px 1px $color;
  }
}

.questionnaire {
  position: relative;
  overflow: hidden; /**it allows to animate**/
  min-height: 700px;
  border: solid thin #b3b3b3;
  width: 98%;
  margin-bottom: 3%;
  transition: min-height .5s ease-out;

  /**CATEGORIES**/
  @each $key, $color in $category-colors {

    .q-category-#{$key} {
      /*border to the navigation btns*/
      button#q-pointer-active-nav::after {
        border-color: $color;
        opacity: 1;
      }

      /*evaluation icons*/

      .q-icon-evaluation-active {
        i {
          color: $color !important;
        }
      }

      .btn-secondary {
        background: white;
        border: gray solid thin;
        margin: 2px;
        color: black;
      }

      .q-active-ok-btn {
        background: $color;
      }

      .q-no-active-ok-btn {
        background: $q-bg-gray-dark;
      }

      .q-bg-primary-rgba-5 {
        background: rgba($color, 0.5);
      }

      .q-bg-primary-rgba-2 {
        background: rgba($color, 0.2);
      }

      div#radio-group-1 i {
        color: $q-bg-gray-dark;

        label.btn.btn-secondary.active i {
          color: $color !important;
        }
      }

      label.btn.btn-secondary.active i {
        color: $color !important;
      }

      .btn-secondary:not(:disabled):not(.disabled):active, .btn-secondary:not(:disabled):not(.disabled).active,
      .show > .btn-secondary.dropdown-toggle {
        color: #fff;
        background-color: $color;
        border-color: unset;

      }

      .q-color-primary {
        color: $color;
      }

      .custom-checkbox .custom-control-input:checked ~ .custom-control-label::after {
        background: $color !important;
        text-align: center;
        color: white;
        font-size: .9em;
        line-height: unset;
        top: 1px;
        left: 1px;
      }

      /**other choice*/
      div#q-reason.q-active-other {
        border: solid thin $color !important;
      }

      input#q_other_choice {
        border-bottom: solid thin $color !important;
      }

      .q-other-choice-wrapper {
        #q-reason {


          .custom-control-label {
            font-size: .9em;

            &::before {
              width: 1.4rem;
              height: 1.4rem;
              background: white;

            }

            &::after {
              border-color: $color;
            }
          }

          .custom-checkbox .custom-control-input:checked ~ .custom-control-label::after {
            content: "X" !important;
            background: transparent !important;
            color: $color;
            top: 4px;
            left: -21px;
            font-weight: bold;
          }

          .custom-checkbox .custom-control-input .custom-control-label::before {

          }
        }
      }


      div#checkbox-group-1 {
        background: white;

        > div {
          background: rgba($color, 0.2);
          border-color: gray;

          label {
            &::before {

            }
          }
        }
      }

      div#q-reason {

      }

      .q-bg-primary {
        background: $color !important;
      }

      i {
        color: $color;
      }

    }
  }

  /*was saved*/
  .q-was-saved {
    z-index: 1000;
  }

  /*focus-visible: navigation btns*/
  .q-navigation-btns-wrapper {
    button {

      &:focus-visible,
      &:focus,
      &:focus-within,
      &:active {
        outline: none !important;
        box-shadow: none !important;
      }
    }
  }

  /*focus opinion scale*/
  #radio-group-1 {
    label {
      &:focus-visible,
      &:focus,
      &:focus-within,
      &:active {
        outline: none !important;
        box-shadow: none !important;
      }


      input {
        &:focus {

        }
      }
    }
  }

  /*grow div*/
  .q-grow-div {
    transition: height .4s ease-out;
    height: 0;
  }


  .q-navigation-btns-wrapper {
    button.q-squares-btns {
      position: relative;

      i {
        position: absolute;
        top: 3px;
        left: 4px;
        color: rgba(255, 255, 255, .7);
        font-size: .7em;
      }

    }
  }

  /*yes no*/

  .q-yes-no {
    label.btn.btn-secondary {

      &::before {
        position: absolute;
        top: 2px;
        left: 3px;
        font-size: .8em;
        border: thin solid;
        padding: 1px;
        line-height: 13px;
      }

      &:nth-child(1) {
        padding-left: 22px;
        padding-right: 19px;

        &::before {
          content: "J";
        }
      }

      &:nth-child(2) {
        padding-left: 22px;
        padding-right: 12px;

        &::before {
          content: "N";
        }
      }
    }

  }

  /*pages*/


  /*start icon*/
  .q-icon-start {
    font-size: 6em;
  }

  /*font size*/
  h4 {
    font-size: 1.1em;
  }

  h5 {
    font-size: 1em;
  }

  /*content wrapper*/
  .q-content-wrapper {
    padding-top: 11%;
  }

  /*icon size*/
  .q-icon-lg {
    font-size: $q-icon-lg;
  }

  .q-icon-md {
    font-size: $q-icon-md;
  }

  .q-icon-sm {
    font-size: $q-icon-sm;
  }

  /*font size*/
  .p-text-size-lg {
    font-size: $p-text-size-lg;
  }

  .q-width-4m {
    width: 4em;
  }

  .q-height-4m {
    height: 4em;
  }


  .p-text-size-md {
    font-size: $p-text-size-md;
  }

  /*position*/
  .q-pos-relative {
    position: relative;
  }

  /*colors*/
  .q-bg-black {
    background: black;
  }


  .q-color-gray {
    color: $q-color-gray;
  }

  .q-bg-gray {
    background: $q-bg-gray;
  }

  .q-bg-gray-dark {
    background: $q-bg-gray-dark;
  }

  .q-bg-gray-radial {
    background: rgb(212, 211, 211);
    background: radial-gradient(circle, rgba(212, 211, 211, 1) 0%, rgba(181, 181, 203, 1) 100%, rgba(0, 212, 255, 1) 100%);
  }

  /*Title*/
  .q-title {
    color: $white;
    padding: .2em .5em;
    font-size: 1.3em;
  }


  /*other*/
  .q-right-0 {
    right: 0;
  }

  .q-opacity-2 {
    opacity: .2;
  }

  .q-opacity-4 {
    opacity: .4;
  }

  .q-opacity-5 {
    opacity: .5;
  }

  .q-opacity-6 {
    opacity: .6;
  }

  /**spinner loading*/
  .q-spinner {
    font-size: 1em;
    height: 2em;
    width: 2em;
    color: gray;
  }

  /**spinner saving**/
  .q-spinner-saving {
    height: 67vh;
    width: 100%;
    z-index: 3000;
    top: 0;
    right: 0;
    left: 0;
    background: white;
  }


  /**Multiple component**/
  div#checkbox-group-1 {
    .custom-control-input:checked ~ .custom-control-label::before {
      border-color: unset;
    }

    .custom-control-label {
      width: 100% !important;
      display: flex;

      span {
        line-height: initial;
        vertical-align: bottom;
        font-size: .8em;
        display: inline-block;
        width: 90%;
        margin-top: auto;
        margin-bottom: auto;
      }
    }
  }

  /**FormalQuestion component**/
  .q-formal-question {
    span {
      display: inline-block;
      width: 93%;
    }
  }


  /*Opinion scale*/
  .q-labels-mobil {
    display: none;
  }

  div#checkbox-group-1 div:nth-child(1) label::before {
    content: "A" !important;
  }

  .custom-checkbox:nth-child(1) .custom-control-input:checked ~ .custom-control-label::after {
    content: "A" !important;
  }

  div#checkbox-group-1 div:nth-child(2) label::before {
    content: "B";
  }

  .custom-checkbox:nth-child(2) .custom-control-input:checked ~ .custom-control-label::after {
    content: "B" !important;
  }

  div#checkbox-group-1 div:nth-child(3) label::before {
    content: "C";
  }

  .custom-checkbox:nth-child(3) .custom-control-input:checked ~ .custom-control-label::after {
    content: "C" !important;
  }


  div#checkbox-group-1 div:nth-child(4) label::before {
    content: "D";
  }

  .custom-checkbox:nth-child(4) .custom-control-input:checked ~ .custom-control-label::after {
    content: "D" !important;
  }


  div#checkbox-group-1 div:nth-child(5) label::before {
    content: "E";
  }

  .custom-checkbox:nth-child(5) .custom-control-input:checked ~ .custom-control-label::after {
    content: "E" !important;
  }


  div#checkbox-group-1 div:nth-child(6) label::before {
    content: "F";
  }

  .custom-checkbox:nth-child(6) .custom-control-input:checked ~ .custom-control-label::after {
    content: "F" !important;
  }


  div#checkbox-group-1 div:nth-child(7) label::before {
    content: "G";
  }

  .custom-checkbox:nth-child(7) .custom-control-input:checked ~ .custom-control-label::after {
    content: "G" !important;
  }


  div#checkbox-group-1 div:nth-child(8) label::before {
    content: "H";
  }

  .custom-checkbox:nth-child(8) .custom-control-input:checked ~ .custom-control-label::after {
    content: "H" !important;
  }


  div#checkbox-group-1 div:nth-child(9) label::before {
    content: "I";
  }

  .custom-checkbox:nth-child(9) .custom-control-input:checked ~ .custom-control-label::after {
    content: "I" !important;
  }


  div#checkbox-group-1 div:nth-child(10) label::before {
    content: "J";
  }

  .custom-checkbox:nth-child(10) .custom-control-input:checked ~ .custom-control-label::after {
    content: "J" !important;
  }


  div#checkbox-group-1 div {
    border: thin #d5d4d4 solid;
    padding: .3em;
    margin: 2px 0 2px 0;

    .custom-control-label::before {
      position: static;
      display: inline-block;
      width: $q-checkbox-sq;
      height: $q-checkbox-sq;
      pointer-events: none;
      font-size: .9em;
      vertical-align: top;
      color: dimgrey;
      text-align: center;
      background-color: white;
      margin-right: 1em;
    }

    .custom-checkbox .custom-control-input:checked ~ .custom-control-label::after {
      position: absolute;
      top: 0;
      width: 1.3em;
      height: 1.3em;
      text-align: center;
      color: white !important;
    }

    .custom-control-label::after {
      position: absolute;
      top: 1px;
      left: 1px;
      display: block;
      width: 1.3em;
      height: 1.3em;
      content: "";
      background: no-repeat 50% / 50% 50%;
    }

  }


  /*border to the navigation btns*/
  .q-squares-btns::after {
    transition: all .6s ease;
    content: "";
    position: absolute;
    width: 100%;
    bottom: -6px;
    left: 0;
    opacity: 0;
    border-bottom: transparent solid;
  }

  /*disabled checkbox groups: reason*/
  .q-disabled-checkbox {
    > div {
      background: $q-checkbox-group-disabled !important;
      border-color: gray !important;
    }

    label {
      &::before {
        background: $q-color-gray !important;
        color: white !important;
      }
    }

  }


}

/*grow the container to fit the question options*/
/*desktop*/
$q-min-height-base-desktop: 730;
@for $key from 1 through 20 {
  $q-min-height-base-desktop: $q-min-height-base-desktop+ 33;
  .questionnaire.q-grow-desktop-#{$key} {
    min-height: $q-min-height-base-desktop+px;
  }
}


.animate-questionnaire-back {
  //margin-top: 100px;
  animation-name: back_;
  animation-duration: .5s;
}

.animate-questionnaire-next {
  animation-name: next;
  animation-duration: .5s;
}


@keyframes back_ {
  from {
    top: -1500px;
  }
  to {
    top: 0;
  }
}

@keyframes next {
  from {
    top: 1500px;
  }
  to {
    top: 0;
  }
}

.questionnaire.q-grow {
  .q-content-wrapper {
    // padding-bottom: 10%;
  }
}

/*viewport md*/
@include media-breakpoint-between("sm", "md") {

  .questionnaire {

    input#q_other_choice {
      //bottom: 137px !important;
    }

    .q-content-wrapper {
      padding-top: 20%;
    }

    /*visual nav*/
    .q-visual-nav {
      width: 100%;

      .q-visual-nav-wrapper {
        width: 100%;
      }

      .q-active {
        width: 100%;
      }

      .q-no-active {
        display: none;
      }
    }

    .q-nav-btns {
      right: 8px;
      bottom: 20px;

      button {
        padding: 0;

        i {
          font-size: 16px;
        }
      }
    }
  }

  .questionnaire.q-has-grow {
    .q-content-wrapper {
      padding-top: 16% !important;
    }
  }


}


/*viewport sm*/
@include media-breakpoint-down("xs") {

  .questionnaire.start-q-shrink,
  .questionnaire.group-q-shrink,
  .questionnaire.formal-q-shrink,
  {
    min-height: 450px;
  }

  /*mobil*/
  $q-min-height-base-mobil: 740;
  @for $key from 1 through 20 {
    $q-min-height-base-mobil: $q-min-height-base-mobil + 22;
    .questionnaire.q-grow-mobil-#{$key} {
      min-height: $q-min-height-base-mobil+px !important;
    }
  }


  .questionnaire {
    width: 100%;
    box-shadow: unset !important;

    /*icon for the pages*/
    .q-icon-sm {
      font-size: 3.6em;
      padding-bottom: 6px;
      margin-left: 8px;
    }

    /*q-radio-opinion*/
    .q-radio-opinion {
      margin-top: 12px;

      label {

        padding: 19px;
        font-size: 1em;
        margin-top: 3px !important;
      }
    }

    /*ok btn*/
    .q-ok-btn,
    .q-weiter-btn,
    .q-send-btn,
    .q-start-btn,
    .q-user-a {
      width: 100% !important;
      padding-top: .5em;

      & + p {
        display: none;
      }
    }

    /*free text input*/
    input#q_free_option {
      &::placeholder {
        font-size: .8em;
      }
    }

    /*rating*/
    .q-rating {
      width: 100%;

      label.btn.btn-secondary {
        padding: 7px 0;
        margin: 0;
      }
    }

    /*title start site*/
    .q-start-content-wrapper {

      h4 {
        font-size: 1.1em;
        padding-bottom: .2em !important;
      }

      h5 {
        font-size: 1em;
      }
    }


    /*other choice*/
    input#q_other_choice {
      //width: 76% !important;
      //bottom: 126px !important;
    }


    /*reason*/
    input#q_other_reason {
      width: 81% !important;
    }

    .q-other-choice-wrapper {
      #q-reason {
        /* 'X' by the reason checkbox*/
        .custom-checkbox .custom-control-input:checked ~ .custom-control-label::after {
          top: 8px !important;
        }

        .custom-control-label {
          font-size: .8em !important;
          line-height: normal;
        }
      }
    }


    /*visual nav*/
    .q-visual-nav {
      width: 100%;

      .q-visual-nav-wrapper {
        width: 100%;
      }

      h3 {
        position: relative;
        padding-left: 0;
        padding-right: 0;
      }

      .q-active {
        width: 80%;
      }

      .q-no-active {
        width: 10%;
        color: transparent !important;

        &:after {
          content: "...";
          color: white;
          position: absolute;
          top: 0;
          left: 7px;
        }
      }
    }

    .q-icon-start {
      font-size: 6em;
    }

    div#radio-group-1 i {
      font-size: 2.18em;
    }

    /*Multiple*/
    div#checkbox-group-1 {
      .custom-control-label {
        span {
          font-size: .7em;
          display: inline-block;
          width: 84%;
        }
      }
    }

    .q-nav-btns {
      right: 8px;
      bottom: 8px;

      button {
        padding: 0;

        i {
          font-size: 16px;
        }
      }
    }

    .q-labels-mobil {
      display: block;
      margin: 0;
      font-size: .8em;
      line-height: initial;
    }

    h4 {
      font-size: .9em;
    }

    h5 {
      font-size: .8em;
    }

    .q-labels {
      display: none;
    }

    .q-content-wrapper {
      padding-top: 24%;
      /*hidden by mobil*/
      .q-navigation {
        .q-navigation-btns-wrapper,
        .q-progress-bar {
          display: none !important;
        }

        .q-nav-mobil {
          display: block;
        }
      }

      .q-radio-opinion {

        display: -webkit-box !important;
        display: -moz-box !important;
        display: -ms-flexbox !important;

        label.btn.btn-secondary {
          width: 23.5%;
        }
      }

    }
    .q-is-school-board {
      h3.q-no-active  {
        width: 20% !important;
      }
    }
  }

  /*patch*/
  .q-has-grow .q-content-wrapper {
    padding-bottom: 20% !important;
  }

}


</style>