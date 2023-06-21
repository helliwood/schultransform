<template>
  <div>
    <div class="mb-4" v-if="getCode">
      <!--  hr & title -->
      <div class="mt-1 mb-2 tr-line-modal">
        <p>Persönlichen Code zusenden</p>
      </div>

      <p>Nur mit Ihrem anonymen Zugangscode können Sie
        auf Ihre Ergebnisse, Handlungsempfehlungen und
        die Potenzialanalyse zugreifen.</p>

      <p>Bitte notieren Sie Ihren Zugangscode oder nutzen
        Sie die Möglichkeit, sich diesen jetzt per E-Mail
        zuzusenden.</p>

      <div class="w-75">
        <b-input-group>
          <b-form-input type="text" :value="getCode" disabled>
          </b-form-input>
          <b-input-group-append>
            <b-button variant="info"
                      :href="'mailto:?subject=Zugang Schultransform.org&body=Vielen%20Dank%2C%20dass%20Sie%20Schultransform.org%20nutzen.%20Ihr%20generierter%20Zugangscode%20f%C3%BCr%20die%20Frageb%C3%B6gen%20und%20die%20Auswertung%20lautet%3A%20'+getCode+'%0A%0ASchultransform%20kann%20auch%20nach%20Zusendung%20dieser%20E-Mail%20Ihren%20Code%20keiner%20Person%20zuordnen.%20Sie%20arbeiten%20vollst%C3%A4ndig%20anonym.%0A%0A-----%0A%0AProjektb%C3%BCro%20Schultransform%0A%0Ac%2Fo%20Helliwood%20media%20%26%20education%20im%20fjs%20e.V.%0AMarchlewskistra%C3%9Fe%2027%2C%2010243%20Berlin%0ATelefon%3A%20%2B49%2030%202938%201680%0ATelefax%3A%20%2B49%2030%202938%201689%0AE-Mail%3A%20support%40schultransform.org%0AInternet%3A%20https%3A%2F%2Fwww.schultransform.org%0A%0AF%C3%B6rderverein%20f%C3%BCr%20Jugend%20und%20Sozialarbeit%20e.V.%20%28fjs%29%0AGesch%C3%A4ftsf%C3%BChrender%20Vorstand%3A%20Thomas%20Schmidt%0AVereinsregisternummer%3A%20VR%2011338%20B%2C%20Amtsgericht%20Berlin-Charlottenburg%0ATransparenzdatenbank%0A%0AF%C3%B6rderung%0ADas%20Forschungs-%20und%20Entwicklungsprojekt%20zur%20Entwicklung%20einer%20Plattform%20zur%20digitalen%20Schultransformation%20%5BSchulTransform%5D%20wird%20gef%C3%B6rdert%20durch%20das%20Bundesministerium%20f%C3%BCr%20Bildung%20und%20Forschung%20%28BMBF%29%20und%20wird%20von%20Helliwood%20media%20%26%20education%20und%20dem%20B%C3%BCndnis%20f%C3%BCr%20Bildung%20gemeinsam%20umgesetzt.%20'">
              Zusenden
            </b-button>
            <b-button variant="info"
                      :href="'mailto:?subject=Zugang Schultransform.org&body=Vielen%20Dank%2C%20dass%20Sie%20Schultransform.org%20nutzen.%20Ihr%20generierter%20Zugangscode%20f%C3%BCr%20die%20Frageb%C3%B6gen%20und%20die%20Auswertung%20lautet%3A%20'+getCode+'%0A%0ASchultransform%20kann%20auch%20nach%20Zusendung%20dieser%20E-Mail%20Ihren%20Code%20keiner%20Person%20zuordnen.%20Sie%20arbeiten%20vollst%C3%A4ndig%20anonym.%0A%0A-----%0A%0AProjektb%C3%BCro%20Schultransform%0A%0Ac%2Fo%20Helliwood%20media%20%26%20education%20im%20fjs%20e.V.%0AMarchlewskistra%C3%9Fe%2027%2C%2010243%20Berlin%0ATelefon%3A%20%2B49%2030%202938%201680%0ATelefax%3A%20%2B49%2030%202938%201689%0AE-Mail%3A%20support%40schultransform.org%0AInternet%3A%20https%3A%2F%2Fwww.schultransform.org%0A%0AF%C3%B6rderverein%20f%C3%BCr%20Jugend%20und%20Sozialarbeit%20e.V.%20%28fjs%29%0AGesch%C3%A4ftsf%C3%BChrender%20Vorstand%3A%20Thomas%20Schmidt%0AVereinsregisternummer%3A%20VR%2011338%20B%2C%20Amtsgericht%20Berlin-Charlottenburg%0ATransparenzdatenbank%0A%0AF%C3%B6rderung%0ADas%20Forschungs-%20und%20Entwicklungsprojekt%20zur%20Entwicklung%20einer%20Plattform%20zur%20digitalen%20Schultransformation%20%5BSchulTransform%5D%20wird%20gef%C3%B6rdert%20durch%20das%20Bundesministerium%20f%C3%BCr%20Bildung%20und%20Forschung%20%28BMBF%29%20und%20wird%20von%20Helliwood%20media%20%26%20education%20und%20dem%20B%C3%BCndnis%20f%C3%BCr%20Bildung%20gemeinsam%20umgesetzt.%20'"
                      class="tr-border-radius-right"><i class="fad fa-external-link"></i></b-button>
          </b-input-group-append>
        </b-input-group>
      </div>

    </div>


    <div v-if="!hasSchool" class="w-100">
      <!--  hr & title -->
      <div class="mt-1 mb-2 tr-line-modal">
        <p>Ihre Schule verknüpfen
        </p>
      </div>

      <p>Verknüpfen Sie Ihre Momentaufnahme mit Ihrer Schule, damit Ihre Fragebogen-Ergebnisse in die Potenzialanalyse
        einfließen. Geben Sie dazu den Schulcode ein, den Sie von Ihrer Schule erhalten haben.</p>

      <p>Liegt Ihnen noch kein Schulcode vor, können Sie hier ganz einfach Ihre Schule
        <b-link href="/PublicUser/register-school"><b><u>registrieren</u></b></b-link>
        .
      </p>
      <div class="w-75">
        <div role="group" class="input-group"><!---->
          <input v-model="schoolcode" class="form-control">
          <div class="input-group-append">
            <button v-on:click="generate" type="button" class="btn tr-border-radius-right btn-primary school-bg-color">
              verknüpfen
            </button>
          </div>
          <div class="w-100 d-block mt-grid">
            <b-alert class="w-100" :show="showError" variant="danger" dismissible>{{ error }}</b-alert>
            <b-alert class="w-100" :show="showInfo" variant="success" dismissible>{{ message }}</b-alert>
          </div>
        </div>
      </div>
    </div>
    <div v-if="hasSchool" class="w-100">
      <!--  hr & title -->
      <div class="mt-1 mb-2 tr-line-modal">
        <p>Ihre Schule</p>
      </div>
      <div class="d-flex">
        <div class="mr-3">
          <div class="position-relative mt-1">
            <i class="fad fa-school school-color tr-display-4"></i>
            <span class="tr-times-mark text-primary"></span>
          </div>
        </div>

        <div class="w-100">
          <p class="  m-0"><strong>{{ this.data.school.name }}</strong></p>
          <p class=" ">
            <span v-if="getRegDate">Mitglied seit: {{ getRegDate }}</span>
            <span v-if="this.data.school && (getRegDate) && this.data.school.confirmed">  /</span>
            <span v-if="this.data.school.confirmed">  verifiziert</span>
          </p>
          <div class="tr-bg-color-gray-200 p-2 d-inline">{{ this.data.school.code }}</div>
        </div>

      </div>
      <div class="mt-grid">
        <p>Sie können jetzt ganz einfach weitere Kolleg:innen einladen. Geben Sie die E-Mail-Adressen ein oder laden Sie
          sich das <a href="/print-teacher-invitation" target="_blank">PDF-Dokument</a> herunter und geben Sie es
          weiter.</p>
        <div class="w-75">
          <div role="group" class="input-group">
            <input v-model="mailRecipients" class="form-control" placeholder="E-Mail Adresse(n) eingeben">
            <div class="input-group-append">
              <button v-on:click="sendMail" type="button"
                      :disabled="mailWasSent"
                      class="btn tr-border-radius-right btn-primary school-bg-color ">Einladen
              </button>
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
          <div><i class="fad fa-shield-check tr-display-4  text-primary"></i></div>
          <div class="ml-2"><a href="#"></a><a href="/datenschutz">Datenschutz ist uns wichtig</a>.
            Bitte teilen Sie uns deshalb nie Ihren persönlichen Code mit. Wir werden Ihre Fragen und Anliegen auch ohne diese Information lösen.
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
  name: "CodeSend",
  props: {
    data: null,
  },
  data() {
    return {
      schoolcode: null,
      mailRecipients: null,
      error: null,
      message: null,
      apiurl: '/link-school',
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
    if (this.data.schoolAuthority) {
      this.apiurl = '/link-school-authority';
    }
  },
  computed: {
    getCode() {
      if (this.data && (this.data.code)) {
        return this.data.code;
      }
      return null;
    },
    hasSchool() {
      if (this.data.school && (this.data.school.code != "")) {
        return true;
      } else {
        return false;
      }
    },
    getRegDate() {
      if (this.data.hasOwnProperty("registrationDate") && this.data.registrationDate != null) {
        if (this.data.registrationDate.hasOwnProperty("date")) {
          let date = new Date(this.data.registrationDate.date);
          return date.toLocaleDateString('de');
        } else {
          return null;
        }
      } else {
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
      if (this.mailRecipients === null || this.mailRecipients === "") {
        // Prevent modal from closing
        this.mailError = "Bitte geben Sie eine E-Mail-Adresse an.";
        this.mailShowError = true;
      } else {
        this.mailError = null;
        this.mailShowError = false;
        this.mailShowInfo = false;
        const data = {"recipients": this.mailRecipients}

        //disable the send button
        this.mailWasSent = true;

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
            //able send button if a error happen in order to allow the user to click
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
