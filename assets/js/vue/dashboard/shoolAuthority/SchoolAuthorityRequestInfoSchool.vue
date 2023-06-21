<template>
  <div>
    <div v-if="hasSchoolAuthority" class="w-100">
      <!--  hr & title -->
      <div class="mt-1 mb-2 tr-line-modal">
        <p>Ihre Schulträger</p>
      </div>
      <div class="mt-grid">
        <p>Schule: {{ getSchoolName }}</p>
        <p>
          Sie können bei der Schule eine Anfrage stellen,
          um weitere Informationen zu erhalten.
          Über diese Funktion erhält der Schulleiter
          eine E-Mail mit der
          Anfrage nach weiteren Informationen.
        </p>
      </div>
      <div class="d-flex justify-content-end my-2">
        <b-button variant="primary" @click="sendMail()" :disabled="mailSent">
          <span>Mail mit Anfrage senden</span>
          <i class="fad fa-paper-plane"></i>
        </b-button>
      </div>
    </div>
    <div class="w-100 d-block mt-grid">
      <b-alert class="w-100" :show="mailShowError" variant="danger" dismissible v-html="mailError"></b-alert>
      <b-alert class="w-100" :show="mailShowInfo" variant="success" dismissible>{{ mailMessage }}</b-alert>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "SchoolAuthorityRequestInfoSchool",
  props: {
    data: null,
    school: null,
  },
  data() {
    return {
      mailApiurl: '/Dashboard/School-Authority/request-school-more-info',
      mailError: null,
      mailMessage: null,
      mailShowError: false,
      mailShowInfo: false,
      mailSent: false,
    }
  },
  mounted() {
  },
  computed: {
    existSchool() {
      let toReturn = false;
      if (this.data && this.data.schoolDataModal) {
        toReturn = true;
      }
      return toReturn;
    },
    getSchoolId() {
      let toReturn = null;
      if (this.data && this.data.schoolDataModal) {
        toReturn = this.data.schoolDataModal.id;
      }
      return toReturn;
    },
    getSchoolName() {
      let toReturn = null;
      if (this.data && this.data.schoolDataModal) {
        toReturn = this.data.schoolDataModal.name;
      }
      return toReturn;
    },
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
  },
  methods: {
    sendMail: function () {
      if (!this.existSchool) {
        return false;
      }
      let data = this.user;
      let promise = axios.post(this.mailApiurl,
          'schoolId=' + this.getSchoolId
          , {
            headers: {
              'Content-Type':
                  'application/x-www-form-urlencoded'
            },
          });
      promise.then((data) => {
        const resp = data.data;
        this.mailSent= true;
        if (resp.success) {
          this.mailMessage = resp.message;
          this.mailShowInfo = true;
          this.mailShowError = false;
        } else {
          this.mailError = resp.error;
          this.mailShowError = true;
          this.mailShowInfo = false;
          // setTimeout(() => location.reload(), 2000);
        }
      }).catch(error => {
        console.error(error);
      });
    }
  }
}
</script>

<style scoped>

</style>