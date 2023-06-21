<template>
  <div>
    <div class="input-group">
      <label for="inputPassword" class="sr-only">Code</label>
      <input v-if="!generateCode" type="textfield" name="code" id="inputPassword" class="form-control"
             placeholder="Code eingeben/generieren" v-model="input" required>
      <div class="input-group-append pb-1">
        <button class="btn btn-school" type="button" id="code-generater" v-b-modal.createCode :disabled="disabler"
                :class="disabler">Code generieren
        </button>
      </div>
    </div>
    <div class="row">
      <div class="col-9 d-flex flex-column">
        <div class="form-check">
          <input type="checkbox" name="save_code" value="1" class="form-check-input" checked="checked"/>
          <label for="contact_dsgvo" class="form-check-label required">Code speichern</label></div>
      </div>
    </div>
    <div class="row">
      <div class="col-9 d-flex flex-column">
        <input class="btn btn-lg big my-1" ref="submitBtn" :class="className" :disabled="!disabler" type="submit"
               value="Jetzt loslegen*"/>
      </div>
    </div>

    <b-modal id="createCode"
             title="Code generieren"
             @ok="generate"
             ok-variant="primary"
             ok-title="Code generieren"
             cancel-title="Abbrechen" cancel-variant="primary-light">
      Ihre Schulform:
      <select v-model="schoolType" class="form-control" required>
        <option :value="null">-- Bitte wählen --</option>
        <option v-for="st in schoolTypes" :value="st.name">{{ st.name }}</option>
      </select>
      <div class="text-danger" v-if="error">Sie müssen Ihren Schultyp wählen.</div>
    </b-modal>

    <b-modal v-if="this.school" id="createCodeWithSchool"
             title="Lehrkräfte Code generieren"
             @ok="generateWithSchool"
             ok-variant="primary"
             ok-title="Code generieren"
             cancel-title="Abbrechen" cancel-variant="primary-light">
      Erstellen Sie Ihren individuellen Zugangscode. Dieser ist direkt mit Ihrer Schule
      <strong>"{{ this.school.name }}"</strong> verknüpft.
    </b-modal>


    <small>* Vergessen Sie nicht, sich Ihren Code zu notieren. Nutzen Sie alternativ unsere Vorlage, sich via <a
        :href="mailto">E-Mail</a> den Code selbst zuzusenden. </small>
  </div>
</template>

<script>
import axios from "axios";
import qs from "qs";
import Vue from "vue";

export default {
  name: "user-login",
  props: {
    apiUrl: String,
    schoolTypes: Array,
    school: Object,
    code: String
  },
  data() {
    return {
      input: "",
      schoolType: null,
      generateCode: false,
      className: "btn-lightgray",
      disabler: false,
      error: false,
      mailto: '',
      mailto1: 'mailto:?subject=Zugang Schultransform.org&body=Vielen%20Dank%2C%20dass%20Sie%20Schultransform.org%20nutzen.%20Ihr%20generierter%20Zugangscode%20f%C3%BCr%20die%20Frageb%C3%B6gen%20und%20die%20Auswertung%20lautet%3A%20',
      mailto2: '%0A%0ASchultransform%20kann%20auch%20nach%20Zusendung%20dieser%20E-Mail%20Ihren%20Code%20keiner%20Person%20zuordnen.%20Sie%20arbeiten%20vollst%C3%A4ndig%20anonym.%0A%0A-----%0A%0AProjektb%C3%BCro%20Schultransform%0A%0Ac%2Fo%20Helliwood%20media%20%26%20education%20im%20fjs%20e.V.%0AMarchlewskistra%C3%9Fe%2027%2C%2010243%20Berlin%0ATelefon%3A%20%2B49%2030%202938%201680%0ATelefax%3A%20%2B49%2030%202938%201689%0AE-Mail%3A%20support%40schultransform.org%0AInternet%3A%20https%3A%2F%2Fwww.schultransform.org%0A%0AF%C3%B6rderverein%20f%C3%BCr%20Jugend%20und%20Sozialarbeit%20e.V.%20%28fjs%29%0AGesch%C3%A4ftsf%C3%BChrender%20Vorstand%3A%20Thomas%20Schmidt%0AVereinsregisternummer%3A%20VR%2011338%20B%2C%20Amtsgericht%20Berlin-Charlottenburg%0ATransparenzdatenbank%0A%0AF%C3%B6rderung%0ADas%20Forschungs-%20und%20Entwicklungsprojekt%20zur%20Entwicklung%20einer%20Plattform%20zur%20digitalen%20Schultransformation%20%5BSchulTransform%5D%20wird%20gef%C3%B6rdert%20durch%20das%20Bundesministerium%20f%C3%BCr%20Bildung%20und%20Forschung%20%28BMBF%29%20und%20wird%20von%20Helliwood%20media%20%26%20education%20und%20dem%20B%C3%BCndnis%20f%C3%BCr%20Bildung%20gemeinsam%20umgesetzt.%20',
    }
  },
  mounted() {
    if (this.code) {
      this.input = this.code;
    }
    // Wenn Schule vorhanden: Modal anzeigen -> Code erstellen und direkt mit Schule verknüpfen
    if (this.school) {
      console.log(this.school);
      this.$bvModal.show('createCodeWithSchool');
    }
    this.$el.querySelector('input').setAttribute("aria-describedby", "code-generater");
    this.mailto = this.mailto1 + '[Noch kein Code]' + this.mailto2;
  },
  watch: {
    input: function (val, oldVal) {
      this.input = val.toUpperCase();
      if (/^.{4}[-].{4}/.test(val)) {
        this.className = "btn-school";
        this.disabler = true;
      } else {
        this.className = "btn-lightgray";
        this.disabler = false;


      }
    }
  },
  methods: {
    upper(e) {
      console.log(e.target.value);
      e.target.value = e.target.value.toUpperCase();
    },
    generate(bvModalEvent) {
      if (this.schoolType === null) {
        // Prevent modal from closing
        bvModalEvent.preventDefault();
        this.error = true;
      } else {
        const data = {"action": 'newCode', "schoolType": this.schoolType}
        let promise = axios.post(this.apiUrl, qs.stringify(data), {
          headers: {
            'Content-Type':
                'application/x-www-form-urlencoded'
          }
        });
        promise.then((data) => {
          this.input = data.data;
          this.mailto = this.mailto1 + data.data + this.mailto2;
        }).catch(error => {
          console.error(error);
        });
      }
    },
    generateWithSchool(bvModalEvent) {
      console.log(this.school);
      const data = {"action": 'newCode', "schoolType": this.school.schoolType, "schoolCode": this.school.code}
      let promise = axios.post(this.apiUrl, qs.stringify(data), {
        headers: {
          'Content-Type':
              'application/x-www-form-urlencoded'
        }
      });
      promise.then((data) => {
        this.input = data.data;
        this.mailto = this.mailto1 + data.data + this.mailto2;
        Vue.nextTick(() => {
          this.$el.ownerDocument.querySelector('#codeForm').submit();
        });
      }).catch(error => {
        console.error(error);
      });
    }
  }


}
</script>

<style lang="scss" scoped>
.switch {
  cursor: pointer;
}

.switch:hover {
  text-decoration: underline;
}

</style>
