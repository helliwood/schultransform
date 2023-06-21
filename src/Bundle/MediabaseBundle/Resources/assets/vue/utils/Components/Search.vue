<template>
  <div class="my-4">
    <div v-if="storeExist" class="d-flex justify-content-end position-relative">
      <b-form-input list="q-input-list"
                    ref="mb_search"
                    type="text"
                    @keyup="ajaxListShowAutocomplete($event)"
                    autocomplete="off"
                    id="g-input-search"
                    required
                    v-model="searchedWord"
                    placeholder="Mediensuche">
      </b-form-input>
      <b-button class="mx-1" v-if="searchedWord"
                variant="secondary"
                :class="isSearchActive?'mb-search-active': ''"
                @click="resetFilter">
        <i class="fa fa-times-circle"></i>
      </b-button>
    </div>
    <div v-else>
      <p v-if="!storeExist" class="text-danger">
        Die Ressource 'Store' (Vuex) ist nicht verfügbar. Sie ist für den korrekten Betrieb erforderlich.
      </p>
    </div>
  </div>

</template>

<script>
import axios from "axios";

export default {
  name: "Search",
  props: {
    bus: null,
    view: null,
  },
  data() {
    return {
      ajaxAutocomplete: [],
      btnFocusIndex: null,
      emptyText: null,
      searchedWord: null,
      //to verify if the store exist
      storeExist: true,
    }
  },
  created() {
    this.bus.$on('clear-search', (data) => {
      this.searchedWord = '';
    });
  },
  mounted() {
    if (!this.$store) {
      this.storeExist = false;
    }
  },
  watch: {
    searchedWord: function (new_value, old_value) {
      if (new_value === '') {
        if (this.$store) {
          this.$store.dispatch("mediabaseBundleStore/dispatchSearchActive", false);
        }
      }
    },
  },
  computed: {
    isSearchActive() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getSearchActive"];
      }
    },
    getCurrentUrl() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getUrl"];
      }
    },

    getSearchUrl() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getUrlSearch"];
      } else {
        return '/Backend/MediaBase/search/';
      }
    },

  },

  methods: {
    clearSearch: function () {
      this.searchedWord = null;
      if (this.$store) {
        this.$store.dispatch("mediabaseBundleStore/dispatchSearchActive", false);
      }
    },
    resetFilter() {
      this.searchedWord = '';
      this.bus.$emit('search-cleared');
    },
    ajaxListShowAutocomplete: function (e) {

      //emit the event to tall with other component
      this.bus.$emit('search-event', {data: this.searchedWord});

      //if user types arrows do not fire
      if (e.key === 'ArrowRight' ||
          e.key === 'ArrowLeft' ||
          e.key === 'ArrowDown' ||
          e.key === 'ArrowUp' ||
          e.key === 'Enter'
      ) {
        return false;
      }
      //save the value in to the store to used in the other view
      if (this.$store) {
        this.$store.dispatch("mediabaseBundleStore/dispatchSearchWord", this.searchedWord);
      }

      //reset to the last state
      if (this.$store) {
        if (this.searchedWord === '') {
          this.$store.dispatch("mediabaseBundleStore/dispatchSearchActive", false);
          return false;
        }
      }

      //only fires if 1(configurable-> store) chars are typed
      if (this.$store) {
        if(this.searchedWord){
          if (this.searchedWord.length < this.$store.getters["mediabaseBundleStore/getSearchMinChars"]) {
            return false;
          }
        }
      }

      let searchFor = this.searchedWord;

      //set searchActive
      if (this.$store) {
        this.$store.dispatch("mediabaseBundleStore/dispatchSearchActive", true);
      }

      //dispatch the search in the store
      if (this.$store) {
        this.$store.dispatch("mediabaseBundleStore/searchMedia");
      }

    },
    resetDataState: function () {
      if (this.$store) {
        this.$store.dispatch("mediabaseBundleStore/loadDataWithUrl", this.getCurrentUrl);
      }
    },

    hasSpecialCharsGral: function (word) {
      let patter = /[^a-zA-Z|ä|Ä|ö|Ö|ü|Ü|ß]/g;
      return word.match(patter);
    },
    hasSpecialCharsGer: function (word) {
      let patter = /[ä|Ä|ö|Ö|ü|Ü|ß]/g;
      return word.match(patter);
    },
  }
}
</script>

<style lang="scss" scoped>
@import "/assets/scss/backend";

.g-ajax-autocomplete {
  top: 33px;
  left: 0;
  z-index: 300;
  width: 100%;

  ul {
    list-style: none;

    li {
      cursor: pointer;

      &:hover {
        background: $primary;
        color: white;
      }
    }
  }
}

</style>