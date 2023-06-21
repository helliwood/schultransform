<template>
  <div  class=" login main-bg-color justify-content-end tr-sm-pr-grid tr-md-pr-grid col-3 d-flex mr-2 mr-md-0 userstatus" :class="{ 'open' : open }" >
    <a :href="currentUrl" @click="toggleMenu($event)" class="mr-md-1 mx-md-1 tr-w-md-100">

      <div  class="d-none d-sm-block dark-bg-color big-icon" id="login-button">
        <span  v-html="currentLabel"></span>
        <i v-if="usericon" :class="'fad the-icon ' +usericon +' ' +role+'-bg-color'"></i>
        <i v-if="!usericon" :class="'fad the-icon theme-bg-color fa-sign-in'"></i>
      </div>
      <span class="big-icon d-flex d-block d-sm-none">
        <i v-if="usericon"  :class="'fad hf ' +usericon +'  m-auto hide-on-open ' +role+'-color'"></i>
        <i v-if="!usericon" :class="'fad the-icon theme-color m-auto fa-sign-in'"></i>

        <i class="fad hf fa-times m-auto theme-color  show-on-open"></i>
      </span>
    </a>
    <ul v-if="entries" :class="'navbar  main-border ' +role+'-color'">
      <li v-for="(item, i) in this.entries" :class="item.attributes.class">

      <!--TODO: find better solution for Modal-handling-->
        <template v-if="role === 'school_authority'">
          <a
              v-if="item.attributes.modal"
              :target="item.attributes.target ? item.attributes.target : false"
              href="#"
              v-b-modal.modal-profile-school-authority
          >
            <span>{{ item.label }}</span>
            <i :class="item.attributes.icon"></i>
          </a>
        </template>

        <template v-else>
          <a
              v-if="item.attributes.modal"
              :target="item.attributes.target ? item.attributes.target : false"
              href="#"
              v-b-modal.modal-profil
          >
            <span>{{ item.label }}</span>
            <i :class="item.attributes.icon"></i>
          </a>
        </template>

        <a
            v-if="!item.attributes.modal"
            :target="item.attributes.target ? item.attributes.target : false"
            :href="item.uri">
          <span>{{ item.label }}</span>
          <i :class="item.attributes.icon"></i>
        </a>
      </li>
    </ul>

    <b-modal id="linkSchool"
             title="Schule verknüpfen"
             @ok="generate"
             ref="modal"
             ok-variant="primary"
             ok-title="Schule verknüpfen"
             cancel-title="Abbrechen" cancel-variant="primary-light">
      <input v-model="schoolcode" v-if="!message" class="w-100 p-1 text-center mb-grid">
      <div class="text-info  w-100 text-center" v-if="message" v-html="message"></div>
      <div class="text-danger w-100 text-center" v-if="error" v-html="error"></div>

    </b-modal>
  </div>

</template>

<script>
import axios from "axios";
import qs from "qs";

export default {
  name: 'user-status',
  props: {
    url: '',
    label: '',
    entries: Array,
    apiurl: String,
    role: null,
    usericon: '',
  },
  data() {
    return {
      currentUrl: this.url,
      currentLabel: this.label,
      schoolcode: null,
      error: null,
      message: null,
      open: false
    }
  },
  mounted() {
    window.addEventListener("message", (event) => {
      if (event.data.op == "change") {
        this.currentLabel = event.data.label;
        this.currentUrl = event.data.url;
      }
    }, false);
  },
  methods: {

    toggleMenu(event){
      if(this.usericon){
        event.preventDefault();
        this.open = !this.open;
      }

    },
    generate(bvModalEvent) {
      bvModalEvent.preventDefault();
      if (this.schoolcode === null) {
        // Prevent modal from closing
        this.error = "Sie müssen einen Schulcode angeben.";
      } else {
        this.error = null;
        const data = {"schoolCode": this.schoolcode}
        let promise = axios.post(this.apiurl, qs.stringify(data), {
          headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
          },
          withCredentials: true
        });
        promise.then((data) => {
          const resp = data.data;
          if (resp.error) {
            this.error = resp.error;
          } else {
            this.message = resp.message;
            setTimeout(() => this.$refs["modal"].hide(), 2000);
          }
        }).catch(error => {
          console.error(error);
        });
      }
    },
  }
}
</script>

<style scoped lang="scss">


</style>
