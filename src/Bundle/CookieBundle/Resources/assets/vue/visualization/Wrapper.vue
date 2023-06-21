<template>
  <div>
    <!--Cookie content-->
    <component :is="pages[this.page]" :data="dataCookie"></component>

    <!--Buttons-->

    <div class="d-flex w-100 flex-column flex-sm-row justify-content-between">
      <div class="flex-grow-1">
        <b-link
            target="_blank"
            :href="getLinkPageToShow"
            class="text-decoration-none"
        >
          {{ getLinkText }}
        </b-link>
      </div>
          <div class="d-flex flex-column flex-sm-row">
        <b-button
            variant="secondary"
            size="sm"
            @click="changeSettings"
            class="mx-0 mx-sm-1 my-1 my-sm-0"
        >
          {{ getBtnChange }}
        </b-button>
        <b-button
            variant="primary"
            size="sm"
        >
          {{ getBtnAll }}
        </b-button>
      </div>
    </div>

  </div>
</template>

<script>
import Cover from './Cover';
import Settings from './Settings'


export default {
  name: "Wrapper",
  components: {
    // Main,
    // Settings
  },
  props: {
    dataCookie: null,
  },
  data() {
    return {
      page: null,
      pages: {
        'cover': Cover,
        'settings': Settings,
      },
    }
  },
  created() {
  },
  mounted() {
    this.page = 'cover';
  },
  computed: {
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
    getLinkPageToShow(){
      return '/' + this.dataCookie.privacylinkpage;
    },
    getLink() {
      //changeSettings
      if (this.dataCookie && (this.dataCookie.privacylinkpage)) {
        return this.dataCookie.privacylinkpage;
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
    //this function is called when the user wants to change the setting of the cookie
    //or just see them in case of the privacy site.
    changeSettings: function () {
      //set the page to settings
      if (this.page === 'cover') {
        this.page = 'settings';
      } else if (this.page === 'settings') {
        //set the cookie
        //collect all the cookies
        this.modal = false;
      }
    },
    acceptAll: function () {
      //close the modal window
      this.modal = false;
    },
  },
}
</script>

<style lang="scss" scoped>
#cookiebanner {
  p {
    font-size: 25px;
  }
}
</style>