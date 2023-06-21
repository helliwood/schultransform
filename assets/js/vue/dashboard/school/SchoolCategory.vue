<template>
  <div>
      <div v-if="intro">
        <intro-component :categories="data" :bus="bus"></intro-component>
      </div>
      <div v-else class="">
        <div class="mt-1 mb-2 tr-line-modal">
          <p>Ihr Unterstützungspaket</p>
        </div>

        <p>Öffnen Sie Ihr Unterstützungspaket in den jeweiligen Handlungsfeldern.
          <span v-if="!allQuestionnairesFilledOut">
          Oder laden Sie weitere Lehrkräfte ein, um das Unterstützungspaket für noch nicht gestartete Handlungsfelder zusammenstellen zu können.
        </span>
        </p>

        <div v-for="item in this.data" class="my-2">
          <div class="d-flex" :class="item.questionnairesFilledOut?'c-'+item.id:'c-gray'">
            <div class='tr-name-bg tr-sm-w-15 tr-md-w-10 tr-w-5 tr-display-4 mr-1 mr-sm-1 text-center'>
              <i :class="'text-white fad '+item.icon"></i>
            </div>

            <div class="w-50 w-sm-100 pr-0 tr-border-2 flex-grow-1 d-flex flex-column justify-content-center"
                 :class="item.questionnairesFilledOut?'':'tr-name-bg text-white'">
              <div class="pl-1">
                <p class="tr-ellipsis">{{ item.name }}</p>
              </div>
            </div>

            <b-link v-if="item.questionnairesFilledOut"
                    :href="'/Dashboard/School/potential/'+item.id"
                    class="px-2 d-block tr-border-radius-right tr-w-15 tr-md-w-25 d-flex flex-column justify-content-center"
                    :class="'tr-name-bg'">
              <span class="text-white tr-ellipsis">Öffnen</span>
            </b-link>
            <b-link
                v-else
                v-b-modal.modal-profil
                class="px-2 d-block tr-border-radius-right tr-w-15 tr-md-w-25 d-flex flex-column justify-content-center"
                :class="'tr-bg-yellow-1'">
              <span class="tr-color-black-100 tr-ellipsis">Einladen</span>
            </b-link>
          </div>

          <div class="pt-1 tr-margin-l-5 tr-md-margin-l-10">
            <small v-if="item.questionnairesFilledOut" class="d-block pl-2">Stand: {{
                item.creationDate
              }}</small>
            <small v-else class="d-block pl-2 tr-color-black-50">noch nicht gestartet</small>
          </div>
        </div>

      </div>
  </div>

</template>

<script>
import IntroComponent from "./IntroComponent";
import Vue from "vue";
import axios from "axios";

export default {
  name: "SchoolCategory",
  components: {IntroComponent},
  props: {
    data: null,
    id: null,
    category: null,
  },
  data: function () {
    return {
      intro: true,
      canStart: false,
      bus: new Vue(),
    }
  },
  created() {
    let me = this;
    this.bus.$on('interval-cleared', function () {
      me.intro = false;
    });
  },
  beforeMount() {
  },
  mounted() {

  },
  filters: {
    date: function (value) {
      return new Date(value);
    }
  },
  computed: {
    allQuestionnairesFilledOut() {
      let toReturn = false;
      if (this.data) {
        let arrayTemp = [];
        let numberQuestionnaires = this.data.length;
        for (let index in this.data) {
          let obj = this.data[index];
          if (obj.questionnairesFilledOut) {
            arrayTemp.push(obj.id);
          }
        }

        if (numberQuestionnaires === arrayTemp.length) {
          toReturn = true;
        }
      }
      return toReturn;
    },
    getCode() {
      if (this.data && (this.data.code)) {
        return this.data.code;
      }
    }
  },
  methods: {
  },
}
</script>

<style scoped>

</style>
