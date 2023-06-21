<template>
  <div>
    <div v-if="hasSchoolAuthority" class="w-100">
      <!--  hr & title -->
      <div class="mt-1 mb-2 tr-line-modal">
        <p>Ihre Schulträger</p>
      </div>
      <div class="d-flex">
        <div class="mr-3">
          <div class="position-relative mt-1">
            <i class="fad fa-school school-color tr-display-4"></i>
            <span class="tr-times-mark text-primary"></span>
          </div>
        </div>

        <div class="w-100">
          <p class="  m-0"><strong>{{ this.data.schoolAuthority.name }}</strong></p>
          <p class=" ">
            <span v-if="getRegDate">Mitglied seit: {{ getRegDate }}</span>
            <span v-if="this.data.schoolAuthority && (getRegDate) && this.data.schoolAuthority.confirmed">  /</span>
            <span v-if="this.data.schoolAuthority.confirmed">  verifiziert</span>
          </p>
          <div class="tr-bg-color-gray-200 p-2 d-inline">{{this.data.schoolAuthority.code}}</div>
        </div>

      </div>
      <div class="mt-grid">
        <p>Sie können jetzt ganz einfach Ihre Schulen einladen. Geben Sie die E-Mail-Adressen ein oder laden Sie
          sich das <a href="/print-school-invitation" target="_blank">PDF-Dokument</a> herunter und geben Sie es weiter.</p>
        <div class="w-75">
          <div role="group" class="input-group">
            <input v-model="mailRecipients" class="form-control" placeholder="E-Mail Adresse(n) eingeben">
            <div class="input-group-append">
              <b-button v-on:click="sendMail"
                        :disabled="mailWasSent"
                        type="button"
                        class="btn tr-border-radius-right btn-primary school-bg-color ">Einladen
              </b-button>
            </div>
            <br>
            <div class="d-block w-100 smaller-font  pl-2 pt-1">Mehrere E-Mail-Adressen mit Komma trennen</div>
            <div class="w-100 d-block mt-grid">
              <b-alert class="w-100" :show="mailShowError" variant="danger" dismissible v-html="mailError"></b-alert>
              <b-alert class="w-100" :show="mailShowInfo" variant="success" dismissible>{{ mailMessage }}</b-alert>
            </div>
          </div>
        </div>
      </div>
      <div class="mt-grid">
        <div class="d-flex">
          <div>
            <i class="fad fa-shield-check tr-display-4 text-primary"></i>
          </div>
          <div class="ml-2">
            <a href="/datenschutz">Datenschutz ist uns wichtig</a>.
            Bitte teilen Sie uns deshalb nie Ihren persönlichen Code mit. Wir werden Ihre Fragen und Anliegen auch ohne
            diese Information lösen.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import qs from "qs";

export default {
  name: "SchoolAuthorityCode",
  props: {
    data: null,
  },
  data() {
    return {
      schoolcode: null,
      mailRecipients: null,
      error: null,
      message: null,
      apiurl: '/link-school-authority',
      showError: false,
      showInfo: false,
      mailError: null,
      mailMessage: null,
      mailApiurl: '/send-invitation',
      mailShowError: false,
      mailShowInfo: false,
      mailWasSent: false,
    }
  },
  mounted() {
    console.log('this.mailApiurl', this.mailApiurl);
  },
  computed: {
    getCode() {
      if (this.data && (this.data.code)) {
        return this.data.code;
      }
      return null;
    },
    hasSchoolAuthority() {
      if (this.data.schoolAuthority && (this.data.schoolAuthority.code != "")) {
        return true;
      } else {
        return false;
      }
    },
    getRegDate() {
      if (this.data.hasOwnProperty("registrationDate")  && this.data.registrationDate!=null ) {
        if (this.data.registrationDate.hasOwnProperty("date") ) {
          let date = new Date(this.data.registrationDate.date);
          return date.toLocaleDateString('de');
        }else{
          return null;
        }
      }else{
        return null;
      }
    }
  },
  methods: {
    generate(bvModalEvent) {
      bvModalEvent.preventDefault();
      if (this.schoolcode === null || this.schoolcode === "") {
        // Prevent modal from closing
        this.error = "Sie müssen einen Schulcode angeben.";
        this.showError = true;
      } else {
        this.error = null;
        this.showError = false;
        this.showInfo = false;
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
            this.showError = true;
            this.showInfo = false;

          } else {
            this.message = resp.message;
            this.showError = false;
            this.showInfo = true;
            setTimeout(() => location.reload(), 2000);
          }
        }).catch(error => {
          console.error(error);
        });
      }
    },
    sendMail(bvModalEvent) {
      bvModalEvent.preventDefault();
      //disable the send button
      this.mailWasSent = true;
      
      if (this.mailRecipients === null || this.mailRecipients === "") {
        // Prevent modal from closing
        this.mailError = "Bitte geben Sie eine E-Mail-Adresse an.";
        this.mailShowError = true;
      } else {
        this.mailError = null;
        this.mailShowError = false;
        this.mailShowInfo = false;
        const data = {"recipients": this.mailRecipients, "typeOfUser": 'schoolAuthority'}
        let promise = axios.post(this.mailApiurl, qs.stringify(data), {
          headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
          },
          withCredentials: true
        });
        promise.then((data) => {
          const resp = data.data;
          if (resp.error) {
            this.mailError = resp.error;
            this.mailShowError = true;
            this.mailShowInfo = false;
            this.mailWasSent = false;
          } else {
            this.mailMessage = resp.message;
            this.mailShowError = false;
            this.mailShowInfo = true;
            // setTimeout(() => location.reload(), 2000);
          }
        }).catch(error => {
          console.error(error);
        });
      }
    },
  }
}
</script>

<style scoped>

</style>
