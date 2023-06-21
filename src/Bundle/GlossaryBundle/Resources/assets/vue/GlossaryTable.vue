<template>
  <div class="tj-gloss-main-wrapper">
    <letter-list :reload="getReload" :onclicked="myProvider"></letter-list>
    <div>
      <b-form ref="g_search_form" @submit.prevent="searchWord" class="my-3">
        <div class="d-flex justify-content-end position-relative">
          <b-form-input list="q-input-list" @keyup="ajaxListShowAutocomplete"
                        @keyup.down="inputArrowDown"
                        @keyup.up="inputArrowUp"
                        autocomplete="off"
                        id="g-input-search"
                        required
                        v-model="searchedWord"
                        placeholder="Wortsuche">
          </b-form-input>
          <div v-if="ajaxListShow" class="position-absolute gloss-ajax-autocomplete pt-1">
            <p v-if="emptyText" class="text-info p-2"><b>{{ emptyText }}</b></p>
            <b-button-group class="d-flex flex-column">
              <template v-if="ajaxAutocomplete.length > 0" v-for="(foundWord,index) in ajaxAutocomplete">
                <b-button class="mr-1 text-left" :ref="'btn'+index" variant="light"
                          @focus="btnFocusIndex=index"
                          @blur="btnFocusIndex=null"
                          @keyup.down="btnArrowDown(index)"
                          @keyup.right="btnArrowDown(index)"
                          @keyup.up="btnArrowUp(index)"
                          @keyup.left="btnArrowUp(index)"
                          @click="searchedWordById(foundWord.id)" v-html="changeFirstLetters(foundWord.word)">
                </b-button>
              </template>
            </b-button-group>
          </div>
          <b-button type="submit" class="ml-2"><i class="fas fa-search"></i></b-button>
        </div>
      </b-form>
    </div>

    <div class="d-flex justify-content-end my-4">
      <b-button variant="primary" v-b-toggle.g-bar @click="loadCreateTemplate">Neues Wort
      </b-button>
    </div>

    <h5 v-if="showTableTitle && showIt" class="my-4">Wörter im Glossar mit dem Buchstaben: <b>{{
        currentLetterToDisplay
      }}</b>
    </h5>

    <b-table
        v-if="showIt"
        ref="gtable"
        :fields="fields"
        :items="items"
        :per-page="itemsPerPage"
        :current-page="currentPage"
        fixed
    >
      <template v-slot:cell(image)="row">
        <img v-if="row.item.image" :src="'/MediaBasePublic/show/' + row.item.image + '/40x40'">
      </template>
      <template v-slot:cell(actions)="row">
        <a :href="getViewUrl(row.item)" class="btn btn-sm btn-primary mr-1"
           size="sm" target="_blank">
          <i class="fas fa-eye"></i> Anzeigen
        </a>
        <a v-b-toggle.g-bar size="sm"
           @click="loadEditTemplate(row.item)"
           class="btn btn-sm btn-primary mr-1">
          <i class="far fa-edit"></i> Bearbeiten
        </a>
        <a size="sm"
           @click="deleteConfirmation(row.item)"
           class="btn btn-sm btn-danger mr-1">
          <i class="fas fa-trash-alt"></i> <span>{{ deleteText }}</span>
        </a>
      </template>
    </b-table>
    <b-pagination
        v-if="showPaginator"
        align="left"
        v-model="currentPage"
        :total-rows="totalRows"
        :per-page="itemsPerPage"
        first-text="Erste"
        prev-text="Vor"
        next-text="Nächste"
        last-text="Letzte"
        size="sm"
        class="pt-3"
    ></b-pagination>
    <b-sidebar
        :class="'gloss-overflow-hidden'"
        :title="sideTitle"
        @hidden="hiddenSideBar"
        @shown="showSideBar"
        v-model="sideBar"
        id="g-bar"
        ref="sidebar"
        :width="'50%'"
        lazy right shadow backdrop>
      <iframe ref="g_iframe"
              class="h-100 w-100 gloss-iframe"
              :src="iframeSrc"
              allowfullscreen></iframe>
    </b-sidebar>
    <div v-if="!showIt" class="pb-4">
      {{ searchResult }}
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {

  name: "GlossaryTable",
  props: {
    itemsPerPage: null,
  },
  data() {
    return {
      currentPage: 1,
      totalRows: 1,
      displayTableTitle: false,
      loadWordsUnderLetterGroup: false,
      btnFocusIndex: null,
      emptyText: null,
      ajaxListShow: false,
      ajaxAutocomplete: [],
      timeoutId: null,
      sideTitle: null,
      searchResult: null,
      searchedWord: null,
      deleteText: '',
      sideBar: false,
      iframeSrc: null,
      showSidebar: true,
      currentLetter: null,
      getReload: false,
      showIt: false,
      items: [],
      fields: [
        {key: 'word', label: "Wort", sortable: true},
        {key: 'image', label: "Bild"},
        {key: 'actions', label: 'Actions'}
      ],
    }
  },
  mounted() {
    window.addEventListener("message", this.eventHandler);
  },
  created() {
    document.addEventListener("click", this.hideList);
  },
  destroyed() {
    window.removeEventListener("message", this.eventHandler);
    document.removeEventListener("click", this.hideList);
  },
  watch: {
    items: function (new_value, old_value) {
      if (new_value && (new_value.length > 0)) {
        this.showIt = true;
      }

    },
    searchedWord: function (new_value, old_value) {
      {
        if (new_value === "" || old_value === "") {
          this.ajaxListShow = false;
          this.ajaxAutocomplete = [];
        }
      }

    }
  },
  computed: {
    currentLetterToDisplay() {
      if (this.currentLetter) {
        let letter = this.currentLetter;
        if (letter === 'Ae') {
          letter = 'Ä';
        }
        if (letter === 'Ue') {
          letter = 'Ü';
        }
        if (letter === 'Oe') {
          letter = 'Ö';
        }
        if (letter === 'Ss') {
          letter = 'ß';
        }
        return letter;
      }
    },
    showPaginator() {
      if (this.items && (this.items.length > this.itemsPerPage)) {
        return true;
      }
      return false;
    },
    showTableTitle() {
      return this.displayTableTitle;
    },
  },
  methods: {
    hideList: function () {
      if (!this.btnFocusIndex) {
        this.ajaxListShow = false;
        this.ajaxAutocomplete = [];
      }

    },
    inputArrowDown: function () {
      if (this.ajaxAutocomplete.length > 0) {
        let nameBtn = 'btn0';
        this.$refs[nameBtn][0].focus();
      }
    },
    inputArrowUp: function () {
      if (this.ajaxAutocomplete.length > 0) {
        let index = this.ajaxAutocomplete.length - 1;
        let nameBtn = 'btn' + index.toString();
        this.$refs[nameBtn][0].focus();
      }
    },
    btnArrowDown: function (index) {
      if (this.ajaxAutocomplete.length <= index + 1) {
        index = -1;
      }
      index++;
      let nameBtn = 'btn' + index.toString();
      this.$refs[nameBtn][0].focus();
    },
    btnArrowUp: function (index) {
      if (index < 1) {
        index = this.ajaxAutocomplete.length;
      }
      index--;
      let nameBtn = 'btn' + index.toString();
      this.$refs[nameBtn][0].focus();
    },
    eventHandler: function (event) {
      //catch the new word
      if (event.data && (event.data.updateWordId)) {
        this.getReload = true; //in case that the word change its letter group
        this.searchedWord = event.data.updateWordId
        this.searchedWordById(event.data.updateWordId);
      }
      if (event.data && (event.data.newWordId)) {
        this.getReload = true;
        this.searchedWord = event.data.newWordId
        this.searchedWordById(event.data.newWordId);
      }

      //event for the slugify
      if (event.data && (event.data.content && (event.data.content === 'form'))) {
        let element = this.$refs.g_iframe.contentWindow.document.getElementById("glossary_word_word");
        element.addEventListener("keyup", (event) => {
          clearTimeout(this.timeoutId);
          this.timeoutId = setTimeout(() => {
            this.slugify(event.target.value);
          }, 1000);
        });
      }
      switch (event.data.op) {
        case 'closeFrame':
          this.sideBar = false;
          break;
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
    changeFirstLetters: function (value) {
      if (this.searchedWord) {
        if (this.hasSpecialCharsGral(this.searchedWord)) {
          return "<strong>" + value + "</strong>";
        }

        //get the length of the word searched
        let lengthWord = this.searchedWord.length;
        return "<strong>" + value.substring(0, lengthWord) + "</strong>" + value.substring(lengthWord);
      }
    },
    ajaxListShowAutocomplete: function () {
      this.searchResult = null;

      if (!this.searchedWord) {
        return false;
      }
      let searchFor = this.searchedWord;
      if (this.hasSpecialCharsGral(searchFor) || this.hasSpecialCharsGer(searchFor)) {
        searchFor = encodeURIComponent(searchFor);
      }

      axios.get('/Backend/Glossary/word/autocomplete/' + searchFor).then(result => {
        //let element = this.$refs.g_iframe.contentWindow.document.getElementById("glossary_word_slug");
        // element.value = result.data;
        this.ajaxListShow = true;
        if (result.data && (result.data.word && (result.data.word.length))) {
          this.emptyText = null;
          this.ajaxAutocomplete = result.data.word;
        } else {
          this.ajaxAutocomplete = [];
          this.emptyText = 'Es gibt kein Wort unter diesem Begriff: ' + this.searchedWord;
        }
      });
    },

    slugify(word) {
      axios.get('/Backend/Glossary/word/slugify/' + "?word=" + word).then(result => {
        let element = this.$refs.g_iframe.contentWindow.document.getElementById("glossary_word_slug");
        element.value = result.data;
      });
    },
    searchedWordById: function (wordId) {

      this.displayTableTitle = false;
      this.ajaxListShow = false;
      this.searchResult = null;
      this.searchedWord = null;
      this.ajaxAutocomplete = [];
      let promise = axios.get('/Backend/Glossary/word/search/id/' + wordId);
      return promise.then((data) => {
        this.items = data.data.word;
        this.currentPage = 1;
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        return (this.items)
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      });


    },
    searchWord: function () {
      //the word is searched as row text
      let wordToSearch;
      this.ajaxListShow = false;
      this.searchResult = null;
      wordToSearch = this.searchedWord
      this.searchedWord = null;
      this.ajaxAutocomplete = [];

      //do the search
      // Here we don't set isBusy prop, so busy state will be
      // handled by table itself
      // this.isBusy = true
      let promise = axios.get('/Backend/Glossary/word/search/' + wordToSearch);
      return promise.then((data) => {

        if (data.data.word < 1) {
          this.showIt = false;
          this.items = [];
          this.searchResult = 'Es wurden keine Begriffe unter dem Wort gefunden: ' + wordToSearch;

        } else {
          this.items = data.data.word
          //set the letter to load the letter group list(words under the letter) when close the modal window
          this.currentLetter = this.items[0].word.charAt(0).toUpperCase();
        }


        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        return (this.items)
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      });


    },
    deleteConfirmation(item) {
      this.$bvModal.msgBoxConfirm('Sie sicher sind, dass Sie das Wort: ' + item.word + ' aus dem Glossar löschen möchten.', {
        title: 'Bitte bestätigen Sie',
        //size: 'sm',
        //buttonSize: 'sm',
        okVariant: 'danger',
        okTitle: 'Ja',
        cancelTitle: 'Nein',
        footerClass: 'p-2',
        hideHeaderClose: false,
        centered: true
      })
          .then(value => {
            if (value) {
              this.deleteItem(item);
            }
          })
          .catch(err => {
            // An error occurred
          })
    },
    deleteItem: function (item) {
      this.getReload = false;
      let params = {
        id: item.id,
        word: item.word,
      }
      let urlDelete = "Glossary/delete/";
      axios.get(urlDelete + params.id + "/" + params.word).then((data) => {
        if (data.data && (data.data.success)) {
          //check if there are items under the letter
          this.getReload = true;
          if (this.items.length > 1) {
            this.myProvider(this.currentLetter);
          } else {
            //if no more items under the letter group the table will not be displayed
            this.showIt = false;
          }

        }
      });
    },

    hiddenSideBar: function () {

    },
    showSideBar: function () {
      this.getReload = false;
    },

    loadEditTemplate(item) {
      this.sideTitle = 'Wort bearbeiten';
      this.iframeSrc = "Glossary/word/edit/" + item.id;
    },
    loadCreateTemplate() {
      this.sideTitle = 'Neues Wort';
      this.iframeSrc = "Glossary/word/new";
    },
    getViewUrl(item) {
      return "/Glossar/" + item.word.charAt(0).toUpperCase() + "/" + item.slug;
    },


    myProvider(l) {
      // Here we don't set isBusy prop, so busy state will be
      // handled by table itself
      // this.isBusy = true

      let letter = l ? l : 'A';
      let promise = axios.get('/Backend/Glossary/words/' + letter);
      return promise.then((data) => {
        this.currentPage = 1;
        this.items = data.data.items
        this.totalRows = this.items.length;
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        this.currentLetter = letter;

        //show the table title
        this.displayTableTitle = true;

        return (this.items)
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      });
    }
  }

}
</script>

<style lang="scss">
@import "assets/scss/backend";
.tj-gloss-main-wrapper{
  .gloss-iframe{
    border: none !important;
  }
  .gloss-overflow-hidden{
    .b-sidebar-body {
      overflow: hidden !important;
    }
  }
  .gloss-ajax-autocomplete {
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
}


</style>