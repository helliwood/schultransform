<template>
  <!--TODO move this component to childcomponents on the first level-->
  <div v-if="!isLoading" class="position-absolute q-navigation">
    <div class="q-nav-mobil" v-if="getNumberOfPages">
      <p>Seite <b>{{ getNumberOfPages.page }}</b> von <span>{{ getNumberOfPages.totalPages }}</span></p>
    </div>
    <progress-bar :cat-position="getCatPos"></progress-bar>
    <b-row class="q-navigation-btns-wrapper" :class="isSchoolBoard?'q-schoolBoard':''">
      <template v-for="(item,key) in getMatchListNavigation">
        <!--to flag which is a question and assign the q-bg-primary color-->
        <template v-if="item.value[0] === 'group'">
          <span class="q-group"></span>
        </template>
        <button class="q-bg-primary q-squares-btns"
                :id="item.pointer === getPointer?'q-pointer-active-nav':''"
                :class="getPagesVisited.includes(item.pointer)?'':' q-opacity-4'"
                size="sm" @click="changePointer(item.pointer)"
                v-html="getElement(item)"
        >
        </button>
      </template>
    </b-row>
  </div>
</template>

<script>
import ProgressBar from "../childcomponents/ProgressBar";
import Vue from "vue";

export default {
  name: "Navigation",
  props: {
    questionnaireData: null,
  },
  components: {ProgressBar},
  mounted() {
  },
  computed: {
    getUserType() {
      return this.$store.getters["qstore/getUserType"];
    },
    isSchoolBoard() {
      return this.getUserType === 'school_board';
    },

    //get pointer
    getPointer() {
      return this.$store.getters["qstore/getPointer"];
    },


    //get the result: saved questions
    getResult() {
      return this.$store.getters["qstore/getResult"];
    },
    //get the pages visited
    getPagesVisited() {
      return this.$store.getters["qstore/getPagesVisited"];
    },

    getMatchListNavigation() {

      return this.$store.getters["qstore/getMatchListNavigation"];
    },
    getNumberOfPages() {
      let pageList = this.getMatchListNavigation;
      let pointer = this.getPointer;
      //return false if is not object and has no elements
      if (typeof pageList === 'object' &&
          !(Object.keys(pageList).length === 0)
          && typeof pointer === 'number') {
        //count the number of pages
        let numberOfPages = this.getMatchListNavigation.length;
        //create an object to store the pointer and the page number
        let counter = 0;
        let returnObject = {};
        for (let key in pageList) {
          counter++;
          let tempObj = pageList[key];
          if (tempObj.hasOwnProperty('pointer')) {
            Vue.set(returnObject, tempObj.pointer, {
              'page': counter,
              'totalPages': numberOfPages,
            });
          }

        }
        return returnObject[pointer];

      } else {
        return false;
      }

    },
    isLoading() {
      return this.$store.getters["qstore/isLoading"];
    },
    getCatPos() {
      if (this.questionnaireData) {
        return this.questionnaireData.catPosition;
      }
    },
  },
  methods: {
    //get the element
    getElement(item) {
      //verify if it is a question
      if (typeof item.value[item.value.length - 1] === 'object'
          && (item.value[item.value.length - 1].hasOwnProperty('questionId'))) {
        //the questionId was stored at the end of the array and it is an object
        let questionId = item.value[item.value.length - 1].questionId
        if (questionId !== '') {
          //verify if the question was answered
          if (this.getResult.questions[questionId] &&
              (this.getResult.questions[questionId].was_answered)) {
            return "<i class='fad fa-check-square'></i>";
          }
        } else {
          //formal
          if (item.value[0] === 'formal' &&
              typeof this.getResult.formal === 'object' &&
              (this.getResult.formal.length > 0)
          ) {
            return "<i class='fad fa-check-square'></i>";
          }
          if (item.value[0] === 'final'
              && this.getResult.evaluation
              && typeof this.getResult.evaluation === 'number') {
            //rating
            return "<i class='fad fa-check-square'></i>";
          }


        }

        //return default
        return "<i class='fad fa-square'></i>"

      }


    },

    //update the pointer in the store
    changePointer: function (pointer) {

      //set the switchFlagNav to true
      this.$store.dispatch("qstore/changeSwitchFlagNav", true);
      this.$store.dispatch("qstore/updatePointerFromNav", pointer);
    },

  }
  ,
}
</script>

<style lang="scss" scoped>
@import "assets/scss/frontend";

.q-navigation {
  bottom: 20px;
  left: 1em;
  background: white;
  z-index: 200;

  button {
    border: none;
    width: $q-nav-square-size;
    height: $q-nav-square-size;
    margin: 1px;
  }

  .q-schoolBoard{
    button{
      margin: 1.2px !important;
    }
  }
  span.q-group {
    margin-left: 12px;
  }

  .q-nav-mobil {
    display: none;
    position: relative;

    > p {
      position: absolute;
      top: -7px;
      font-size: .6em;
      width: 120px;
    }
  }


}


</style>