<template>
  <div v-if="dataCookie">
    <b-modal
        id="cookiebanner"
        size="lg"
        v-model="modal"
        :no-close-on-esc="true"
        :no-close-on-backdrop="true"
        :close-on-esc="false"
    >
      <!--Close button-->
      <template v-slot:modal-header>

        <div class="d-flex justify-content-between w-100 m-1 m-sm-0 p-0 tr-cookie-modal-header">
          <!--Title-->
          <h5 class="py-1 m-sm-1 m-lg-0 ml-2 ml-sm-0 px-0 px-sm-1 px-lg-0 tr-cookie-h5">{{getModalTitle}}</h5>
          <p class="pr-2 mr-1 tr-cookie-close" size="sm" @click="hideCookieBanner">
            X
          </p>
        </div>
      </template>

      <!--Cookie content-->
      <component :is="pages[this.page]" :data="dataCookie"></component>

      <!--Buttons-->
      <template #modal-footer>
        <div class="d-flex w-100 flex-column flex-sm-row justify-content-between">
          <div class="flex-grow-1">
            <b-link
                target="_blank"
                :href="getLink"
                class="text-decoration-none tr-cookie-privacy-link"
            >
              {{ getLinkText }}
            </b-link>
          </div>
          <div class="d-flex flex-column flex-sm-row">
            <b-button
                class="mr-sm-1 mb-1 mb-sm-0 tr-cookie-btn-settings"
                variant="secondary"
                size="sm"
                @click="changeSettings"
            >
              {{ getBtnChange }}
            </b-button>
            <b-button
                class="tr-cookie-btn-accept-all"
                variant="primary"
                size="sm"
                @click="acceptAll"
            >
              {{ getBtnAll }}
            </b-button>
          </div>

        </div>
      </template>

    </b-modal>
  </div>
</template>

<script>
import Cover from './Cover';
import Settings from './Settings'
import axios from "axios";
import Coostore from "../store/coostore";

export default {
  name: "CookieBanner",
  components: {
    // Main,
    // Settings
  },
  props: {
    bannerId: null,
  },
  data() {
    return {
      //to storage the initial html overflow
      htmlOverflowInitial: '',
      modal: true,
      page: null,
      dataCookie: null,
      classForLinkTrigger: 'cookieAP',
      pages: {
        'cover': Cover,
        'settings': Settings,
      },
    }
  },
  created() {
    //create the 'coostore' if was not created
    if (!this.$store.hasModule("coostore")) {
      this.$store.registerModule("coostore", Coostore);
    }
  },
  mounted() {
    let self = this;
    //set an event to each element found in DOM (those elements can be placed in DOM over a twig template or via snippet).
    //This function is going to look for elements with the class declared in the variable 'classForLinkTrigger'
    // and set an event listener to open the modal window.
    let linksTriggerOpenModal = document.getElementsByClassName(this.classForLinkTrigger);
    if (linksTriggerOpenModal && (typeof linksTriggerOpenModal === 'object' && (Object.keys(linksTriggerOpenModal).length > 0))) {
      for (let index in linksTriggerOpenModal) {
        let linkElement = linksTriggerOpenModal[index];
        if (typeof linkElement === 'object') {
          linkElement.addEventListener('click', function (e) {
            if (e && (e.target)) {
              let attrKey = e.target.getAttribute('data-key');
              self.showModal(attrKey)
            }
          });
        }
      }
    }

    //to configure the cookies
    //set all the time the first page to cover
    this.page = 'cover';

    //make the call and set the data to the sore and
    //in the store is going to perform the operations
    //to set and delete the cookies.

    //var viewOnly is used to differentiate when the user wants
    this.myProvider(false);


  },
  computed: {
    getModalTitle(){
      if (this.page === 'cover') {
        if (this.dataCookie && (this.dataCookie.title)) {
          return this.dataCookie.title;
        } else {
          //fallback
        }
      } else if (this.page === 'settings') {
        if (this.dataCookie && (this.dataCookie.titlesub)) {
          return this.dataCookie.titlesub;
        } else {
          //fallback
        }
      }
    },
    getBtnAll() {
      //take all
      if (this.page === 'cover') {
        if (this.dataCookie && (this.dataCookie.btnmainall)) {
          return this.dataCookie.btnmainall;
        } else {
          //fallback
        }
      } else if (this.page === 'settings') {
        if (this.dataCookie && (this.dataCookie.btnsuball)) {
          return this.dataCookie.btnsuball;
        } else {
          //fallback
        }
      }

    },
    getBtnChange() {
      //changeSettings
      if (this.page === 'cover') {
        if (this.dataCookie && (this.dataCookie.btnmainsettings)) {
          return this.dataCookie.btnmainsettings;
        } else {
          //fallback
        }
      } else if (this.page === 'settings') {
        if (this.dataCookie && (this.dataCookie.btnsubsettings)) {
          return this.dataCookie.btnsubsettings;
        } else {
          //fallback
        }
      }

    },
    getLink() {
      //changeSettings
      if (this.dataCookie && (this.dataCookie.privacylinkpage)) {
        return '/' + this.dataCookie.privacylinkpage;
      } else {
        //fallback
      }
    },
    getLinkText() {
      //changeSettings
      if (this.dataCookie && (this.dataCookie.privacylinktext)) {
        return this.dataCookie.privacylinktext;
      } else {
        //fallback
      }
    },
  },
  methods: {

    //hide cookie banner
    hideCookieBanner: function () {
      this.$bvModal.hide('cookiebanner')

      //return the overflow to the html
      let myHtml = document.getElementsByTagName('html');
      //check if exist the element
      if (myHtml.item(0)) {
        myHtml.item(0).style.overflow = this.htmlOverflowInitial;
      }
    },

    //this function is called when the user wants to change the setting of the cookie
    //and when a snippet contains a class that is the same as 'classForLinkTrigger'
    showModal: function (attrKey) {
      if (attrKey) {
        //send the value to the store
        this.$store.dispatch("coostore/dispatchSetClassHighlighted", attrKey);
        this.page = 'settings';
      } else {
        this.$store.dispatch("coostore/dispatchSetClassHighlighted", null);
        this.page = 'cover';
      }
      //make the call to retrieve the data from the database
      this.myProvider(true);
      this.modal = true;

    },
    changeSettings: function () {
      //set the page to settings
      if (this.page === 'cover') {
        this.page = 'settings';
      } else if (this.page === 'settings') {
        //set the cookie
        this.$store.dispatch("coostore/dispatchSetCookies", this.$cookies);

        //close the modal window
        this.modal = false;
        window.location.reload();
      }

    },
    acceptAll: function () {
      //set all the cookies to true
      this.$store.dispatch("coostore/dispatchSetAllAccept");

      //set the cookie
      this.$store.dispatch("coostore/dispatchSetCookiesAllSelected", this.$cookies);

      //close the modal window
      this.modal = false;
      window.location.reload();
    },
    myProvider: function (viewOnly) {
      // Here we don't set isBusy prop, so busy state will be
      // handled by table itself
      // this.isBusy = true

      //return false if bannerId was not set;
      if (!this.bannerId) {
        console.log('no banner id was set!');
        return false;
      }


      //make the ajax call to the end point
      let promise = axios.get('/Cookie/get/' + this.bannerId,
          {
            headers: {
              // 'Access-Control-Allow-Origin': '*',
              'X-Requested-With': 'XMLHttpRequest',
            },
          });

      return promise.then((data) => {

        //do not show the modal window if the data.data is empty
        if (Object.keys(data.data).length < 1) {
          return false;
        }
        //set the data that is going to be used.
        this.dataCookie = data.data;

        //do not show the window if the cookie 'cookieControlPref' was set and was not pass the viewOnly = true.
        if (this.$cookies.isKey('cookieControlPrefs') && !viewOnly) {
          this.modal = false;
        }

        //once the data is the ask for the name of the Privacy site('datenschutz')
        if (window.location.pathname === '/' + this.dataCookie.privacylinkpage && !viewOnly) {
          //do not show the modal window
          this.modal = false;
        }


        //store the data in the store
        this.$store.dispatch("coostore/dispatchData", data.data);

        if (this.$cookies.isKey('cookieControlPrefs')) {
          //set the data of the cookie in the store
          this.$store.dispatch("coostore/dispatchCookieValues", this.$cookies.get('cookieControlPrefs'))
        }

        //delete the cookie that are not saved on the 'cookieControlPrefs' cookie
        this.$store.dispatch("coostore/dispatchExecuteEraserTasks", this.$cookies);

        //check if the modal is open -> this.modal = true
        if (this.modal) {
          //set the body overflow to hide in order to remove the double scroll
          let myHtml = document.getElementsByTagName('html');
            // check if element was captured
          if (myHtml.item(0)) {
            myHtml.item(0).style.overflow = 'hidden';
          }
        }

        return this.dataCookie;
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // internal busy state in case of error
        return []
      })
    },
  },
}
</script>

<style lang="scss">
#cookiebanner {
  .modal-content{
    padding: 1px 10px;
  }
  .tr-cookie-close{
    font-weight: bold;
    color: gray;
    cursor: pointer;
    padding: 2px;
    opacity: .6;
    &:hover{
      opacity: 1;
    }
  }
}
</style>