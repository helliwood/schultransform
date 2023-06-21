<template>
  <div class="col-lg-8 col-md-11 col-sm-11 m-auto">
    <category-icon class="q-icon-sm mb-1" :category-id="getCategoryId"></category-icon>
    <p class="q-text-final mb-3">Vielen Dank für das anonyme Ausfüllen des Fragebogens.
      Sie können sich gleich Ihr Ergebnis in einer Übersicht ansehen und den Fragebogen mit Ihren Zugangsdaten wiederholt ausfüllen.</p>


    <evaluation></evaluation>
    <send-btn :onpressed="sendQuestionnaire"></send-btn>
    <!--show message that the questionnaire was correctly saved-->
    <b-alert class="mt-4 q-alert-success" v-if="getWasSaved" variant="success" show>Der Fragebogen wurde erfolgreich
      gespeichert. Sie werden umgeleitet...
    </b-alert>

    <b-alert class="mt-4 q-alert-error" v-if="getErrorToDisplay" show variant="danger">{{getErrorToDisplay}}</b-alert>
  </div>
</template>

<script>
import Evaluation from "./childcomponents/Evaluation";
import SendBtn from "./childcomponents/SendBtn";
import CategoryIcon from "../childcomponents/CategoryIcon";
import axios from "axios";

export default {
  components: {
    CategoryIcon,
    Evaluation,
    SendBtn,
  },
  name: "Final",
  data() {
    return {}
  },
  computed: {
    getErrorToDisplay(){
      return this.$store.getters["qstore/getErrorToDisplay"];
    },
    getCategoryId() {
      return this.$store.getters["qstore/getCategoryId"];
    },
    getResult() {
      return this.$store.getters["qstore/getResult"];
    },
    getErrors() {
      return this.$store.getters["qstore/getPropertyError"];
    },
    getWasSaved() {
      return this.$store.getters["qstore/getWasSaved"];
    },
    getUsertype() {
      return this.$store.getters["qstore/getUserType"];
    }
  },
  methods: {
    sendQuestionnaire: function () {
      this.$store.dispatch("qstore/validateQuestionnaire");
      //if no errors then send the questionnaire
      if (Object.keys(this.getErrors).length === 0 && this.getErrors.constructor === Object) {
        this.devClass = null;
        //send the questionnaire to the controller
        //user type = 'school_board'
        if (this.getUsertype && (this.getUsertype === 'school_board')) {
          //add to the results
          this.getResult.userType = this.getUsertype;
        }
        this.$store.dispatch("qstore/saveQuestionnaire", this.getResult);
        //aks if everything went well by saving ann if so the user will be redirect to:
        //-> url: 'PublicUser/user-success'
        //window.location.href = '/PublicUser/user-success';
        /**Now the Store redirects the user when the questionnaire is successfully saved*/

      }
    },

  },
}
</script>

<style scoped>

p.q-text-final {
  font-size: .8em;
  line-height: normal;
  color: #5f5f5f;
}

.q-alert-success,
.q-alert-error
{
  font-size: .8em;
  line-height: normal;
}

</style>