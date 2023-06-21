<template>
  <div>
    <div class="mb-4" v-if="getCode">
      <!--  hr & title -->
      <div class="mt-1 mb-2 tr-line-modal">
        <p>Persönlichen Code zusenden</p>
      </div>

      <p>Nur mit Ihrem generierten Zugangscode können Sie zukünftig auf Ihre Ergebnisse
        und Handlungsempfehlungen zugreifen!</p>

      <p>Bitte notieren Sie Ihren Zugangscode oder nutzen
        Sie die Möglichkeit, sich diesen jetzt per E-Mail
        zuzusenden.</p>

      <div class="w-75">
        <b-input-group>

          <b-form-input type="text" :value="getCode" disabled>
          </b-form-input>

          <b-input-group-append>
            <b-button variant="info" :href="'mailto:?subject=Zugang Schultransform.org&body=Vielen%20Dank%2C%20dass%20Sie%20Schultransform.org%20nutzen.%20Ihr%20generierter%20Zugangscode%20f%C3%BCr%20die%20Frageb%C3%B6gen%20und%20die%20Auswertung%20lautet%3A%20'+getCode+'%0A%0ASchultransform%20kann%20auch%20nach%20Zusendung%20dieser%20E-Mail%20Ihren%20Code%20keiner%20Person%20zuordnen.%20Sie%20arbeiten%20vollst%C3%A4ndig%20anonym.%0A%0A-----%0A%0AProjektb%C3%BCro%20Schultransform%0A%0Ac%2Fo%20Helliwood%20media%20%26%20education%20im%20fjs%20e.V.%0AMarchlewskistra%C3%9Fe%2027%2C%2010243%20Berlin%0ATelefon%3A%20%2B49%2030%202938%201680%0ATelefax%3A%20%2B49%2030%202938%201689%0AE-Mail%3A%20support%40schultransform.org%0AInternet%3A%20https%3A%2F%2Fwww.schultransform.org%0A%0AF%C3%B6rderverein%20f%C3%BCr%20Jugend%20und%20Sozialarbeit%20e.V.%20%28fjs%29%0AGesch%C3%A4ftsf%C3%BChrender%20Vorstand%3A%20Thomas%20Schmidt%0AVereinsregisternummer%3A%20VR%2011338%20B%2C%20Amtsgericht%20Berlin-Charlottenburg%0ATransparenzdatenbank%0A%0AF%C3%B6rderung%0ADas%20Forschungs-%20und%20Entwicklungsprojekt%20zur%20Entwicklung%20einer%20Plattform%20zur%20digitalen%20Schultransformation%20%5BSchulTransform%5D%20wird%20gef%C3%B6rdert%20durch%20das%20Bundesministerium%20f%C3%BCr%20Bildung%20und%20Forschung%20%28BMBF%29%20und%20wird%20von%20Helliwood%20media%20%26%20education%20und%20dem%20B%C3%BCndnis%20f%C3%BCr%20Bildung%20gemeinsam%20umgesetzt.%20'">Zusenden</b-button>
            <b-button variant="info" :href="'mailto:?subject=Zugang Schultransform.org&body=Vielen%20Dank%2C%20dass%20Sie%20Schultransform.org%20nutzen.%20Ihr%20generierter%20Zugangscode%20f%C3%BCr%20die%20Frageb%C3%B6gen%20und%20die%20Auswertung%20lautet%3A%20'+getCode+'%0A%0ASchultransform%20kann%20auch%20nach%20Zusendung%20dieser%20E-Mail%20Ihren%20Code%20keiner%20Person%20zuordnen.%20Sie%20arbeiten%20vollst%C3%A4ndig%20anonym.%0A%0A-----%0A%0AProjektb%C3%BCro%20Schultransform%0A%0Ac%2Fo%20Helliwood%20media%20%26%20education%20im%20fjs%20e.V.%0AMarchlewskistra%C3%9Fe%2027%2C%2010243%20Berlin%0ATelefon%3A%20%2B49%2030%202938%201680%0ATelefax%3A%20%2B49%2030%202938%201689%0AE-Mail%3A%20support%40schultransform.org%0AInternet%3A%20https%3A%2F%2Fwww.schultransform.org%0A%0AF%C3%B6rderverein%20f%C3%BCr%20Jugend%20und%20Sozialarbeit%20e.V.%20%28fjs%29%0AGesch%C3%A4ftsf%C3%BChrender%20Vorstand%3A%20Thomas%20Schmidt%0AVereinsregisternummer%3A%20VR%2011338%20B%2C%20Amtsgericht%20Berlin-Charlottenburg%0ATransparenzdatenbank%0A%0AF%C3%B6rderung%0ADas%20Forschungs-%20und%20Entwicklungsprojekt%20zur%20Entwicklung%20einer%20Plattform%20zur%20digitalen%20Schultransformation%20%5BSchulTransform%5D%20wird%20gef%C3%B6rdert%20durch%20das%20Bundesministerium%20f%C3%BCr%20Bildung%20und%20Forschung%20%28BMBF%29%20und%20wird%20von%20Helliwood%20media%20%26%20education%20und%20dem%20B%C3%BCndnis%20f%C3%BCr%20Bildung%20gemeinsam%20umgesetzt.%20'" class="tr-border-radius-right"><i class="fad fa-external-link"></i></b-button>
          </b-input-group-append>
        </b-input-group>
      </div>

    </div>
  </div>
</template>

<script>
import axios from "axios";
import qs from "qs";

export default {
  name: "TeacherCodeOnly",
  props: {
    data: null,
  },
  data() {
    return {
      schoolcode: null,
      error: null,
      message: null,
      apiurl:'/link-school',
      showError:false,
      showInfo: false
    }
  },
  mounted() {
  },
  computed: {
    getCode() {
      if (this.data && (this.data.code)) {
        return this.data.code;
      }
      return null;
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
            setTimeout(() =>  location.reload() , 2000);
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
