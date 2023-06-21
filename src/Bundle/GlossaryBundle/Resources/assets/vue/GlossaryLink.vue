<template>
  <div class="d-inline-block position-relative g-word-window">
    <div class="d-none d-md-block">
      <b-link class="g-link-dotted mb-2" variant="primary" @click="showToast(getRandomName, $event)">
        <span>{{ getWordDisplayed }}</span>
      </b-link>
      <b-toast :id="getRandomName"
               no-auto-hide
               static
               toast-class="g-toast-window"
               @show="onShow"
               class="position-absolute bg-white g-toast-div"
               :class="makeToastFitInWindow">
        <template v-slot:toast-title>
          <div v-if="isRelatedWord">
            <p class="m-0 g-modal-title g-color-test"><b>{{ getWordDisplayed }}</b></p>
            <p class="m-0 g-color-test">Bedeutung siehe <i class="fad fa-long-arrow-right"></i> {{ getWord }}</p>
          </div>
          <div v-else>
            <p class="m-0 g-modal-title g-color-test"><b>{{ getWordDisplayed }}</b></p>
            <p class="m-0 g-color-test" v-if="getRelatedWords">ähnliche Begriffe: {{ getRelatedWords }}</p>
          </div>
        </template>
        <div class="g-link-wrapper-toast">
          <img class="m-auto" v-if="image" :src="getImage">
          <div class="d-flex justify-content-between flex-column">
            <p class="g-word-description g-color-test">{{ getShortDescription }}</p>
            <b-button variant="primary" :href="getHref">{{ getActionBtnTitle }} {{ getWord }}</b-button>
          </div>
        </div>
      </b-toast>
    </div>
    <div class="d-md-none">
      <b-link class="g-link-dotted" @click="$bvModal.show(getRandomName)">{{ getWordDisplayed }}</b-link>
      <b-modal :id="getRandomName"
               ok-only
               @show="onShow"
               ok-variant="light"
               button-size="sm"
               ok-title="Fenster schließen"
               modal-class="g-modal-window"
               :title="getTitle">
        <template v-slot:modal-header>
          <div v-if="isRelatedWord">
            <p class="pl-2 pt-2 m-0 g-modal-title g-color-test"><b>{{ getWordDisplayed }}</b></p>
            <p class="pl-2 mb-1 g-color-test">Bedeutung siehe <i class="fad fa-long-arrow-right"></i> {{ getWord }}</p>
          </div>
          <div v-else>
            <p class="pl-2 pt-2 m-0 g-modal-title g-color-test"><b>{{ getWordDisplayed }}</b></p>
            <p class="pl-2 pr-1 mb-1 g-color-test" v-if="getRelatedWords">ähnliche Begriffe: {{ getRelatedWords }}</p>
          </div>
        </template>
        <div class="g-link-wrapper-modal">
          <img class="m-auto img-fluid" v-if="image" :src="getImage">
          <div class="d-flex justify-content-between flex-column">
            <p class="g-word-description g-color-test">{{ getShortDescription }}</p>
            <b-button variant="primary" :href="getHref">{{ getActionBtnTitle }} {{ getWord }}</b-button>
          </div>
        </div>
      </b-modal>
    </div>

  </div>
</template>

<script>

import axios from "axios";
import Glosstore from "./store/glosstore";

export default {
  name: "GlossaryLink",
  props: {
    wordDisplayed: null,
    isRelatedWord: null,
    word: null,
    wordId: null,
    slug: null,
    image: null,
    href: null,
    Glosstore,
  },
  data() {
    return {
      alignLeft: false,
      alignBottom: false,
      wasClicked: false,
      shortDescription: null,
      relatedWords: null,
      dataWord: null,
    }
  },
  created() {
    //create the 'qstore' if was not created
    if (!this.$store.hasModule("glostore")) {
      this.$store.registerModule("glostore", Glosstore);
    }
  },
  mounted() {

  },
  destroyed() {
    //clear data in store
    this.$store.dispatch("glostore/dispatchClearData");
  },

  computed: {
    makeToastFitInWindow() {
      let toReturn = '';

      if (this.alignBottom && this.alignLeft) {

        toReturn = 'g-left-align g-bottom-align';

      } else {
        if (this.alignBottom) {
          toReturn = 'g-bottom-align';

        } else if (this.alignLeft) {
          toReturn = 'g-left-align';
        }
      }
      return toReturn;
    },
    getActionBtnTitle() {
      return 'Weiteres im Glossar über:';
    },
    isMobile() {
      if (screen.width <= 410) {
        return true;
      }
    },
    getRandomName() {
      let randomNumber = Math.floor(Math.random() * 10000);
      return this.getWord + '_' + randomNumber;
    },
    getWordDisplayed() {
      return this.wordDisplayed;
    },
    getWord() {
      return this.word;
    },
    getTitle() {
      let title = '';
      if (this.getIsRelatedWord) {
        title = "Das Wort " + this.getWordDisplayed + " ist verbunden mit " + this.getWord;
      } else {
        title = this.getWord;
      }
      return title;
    },
    getIsRelatedWord() {
      return this.isRelatedWord;
    },
    getWordId() {
      return this.wordId;
    },
    getSlug() {
      return this.slug;

    },
    getImage() {
      return "/MediaBasePublic/show/" + this.image + "/300x300";
    },
    getDataStore() {
      return this.$store.getters["glostore/getData"];
    },
    getShortDescription() {
      this.shortDescription = this.$store.getters["glostore/getShortDescription"];
      return this.shortDescription;
    },
    getRelatedWords() {
      this.relatedWords = this.$store.getters["glostore/getRelatedWords"];
      return this.relatedWords;
    },
    getHref() {
      return this.href;
    },
  },
  methods: {
    showToast: function (toastName, event) {


      //if the link is clicked again hide the toast
      if (this.wasClicked === toastName) {
        this.$bvToast.hide(toastName);
      }

      this.$bvToast.show(toastName);

      let self = this;
      let observer = new MutationObserver(function (mutations, me) {

        // `mutations` is an array of mutations that occurred
        // `me` is the MutationObserver instance
        let toast = document.getElementById(toastName);
        if (toast) {
          self.activateCssClasses(toast, event);
          me.disconnect(); // stop observing
          return;
        }
      });

      // start observing
      observer.observe(document, {
        childList: true,
        subtree: true
      });

    },
    getWordData: function (wordId) {
      // Here we don't set isBusy prop, so busy state will be
      // handled by table itself
      // this.isBusy = true
      if (!this.wordId) {
        return false;
      }
      let promise = axios.get('/Glossar/word/id/' + this.wordId);

      return promise.then((data) => {

        this.shortDescription = data.data.shortDescription;
        this.relatedWords = data.data.relatedWords;
        this.dataWord = data;
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        //save the data response in the store
        this.$store.dispatch("glostore/dispatchData", data.data);

        return this.relatedWords;
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      })
    },

    activateCssClasses: function (toast, event) {
      //reset the values every time to avoid unexpected results
      this.alignLeft = false;
      this.alignBottom = false;

      let windowWidth = window.innerWidth;
      let windowHeight = window.innerHeight;

      if (event.x + toast.offsetWidth > windowWidth) {
        this.alignLeft = true;
      }
      if (event.y + toast.offsetHeight > windowHeight) {
        this.alignBottom = true;
      }
      this.wasClicked = toast.id;
    },

    clearAllToast: function (toasts) {
      for (let item of toasts) {
        this.$bvToast.hide(item.id)
      }
    },

    onShow: function () {
      let toasts = document.getElementsByClassName('g-toast-window');
      if (toasts) {
        this.clearAllToast(toasts);
      }
      this.getWordData(this.wordId);
    }
  }


}
</script>

<style lang="scss" scoped>


.g-toast-div {
  z-index: 2000;
}

.g-link-wrapper-toast {
  width: 300px;
}

.g-left-align {
  left: -255px;
}

.g-bottom-align {
  bottom: +22px;
}

.g-color-test {
  color: #383838;
}

.g-modal-title {
  // width: 85%;
}

p.g-word-description {
  overflow: hidden;
  margin: 11px 0;
  line-height: 1.3em;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 5;
  white-space: normal;
  padding: 0px 10px;
}

.g-link-dotted {
  color: black !important;
  border-color: #006495;
  border-bottom: dotted 2px;

  &:hover {
    text-decoration: none !important;
  }
}

</style>