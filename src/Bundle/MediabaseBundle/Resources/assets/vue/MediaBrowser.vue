<template>
  <div>
    <div class="d-flex mb-2">
      <!-- Breadcrumb, new folder, btn change view-->
      <b-breadcrumb v-if="!isLoading">
        <b-breadcrumb-item v-for="item in getBitems" :key="item.text" @click="breadcrumbClick(item)">
          <i v-if="item.icon" :class="item.icon + ' pr-1'"></i>
          {{ item.name }}
        </b-breadcrumb-item>
      </b-breadcrumb>
      <div class="ml-auto">
        <b-button-group>
          <!--currentItem when null = root directory-->
          <b-button v-if="getCurrentItem && !searchActive" size="sm" variant="secondary" @click="newFile"><i
              class="fas fa-file-upload"></i> Upload
          </b-button>
          <!-- Create new folder-->
          <b-button v-if="!searchActive" size="sm" variant="secondary" @click="newFolder"><i
              class="fas fa-folder-plus"></i> Neuer Ordner
          </b-button>
          <!--Icons view-->
          <b-button v-if="renderMode === 'icons'" variant="primary" size="sm" @click="changeView('list')"><i
              class="fas fa-list-ul"></i> Listenansicht
          </b-button>
          <!--List view-->
          <b-button v-if="renderMode === 'list'" variant="primary" size="sm" @click="changeView('icons')"><i
              class="fas fa-grip-vertical"></i> Iconansicht
          </b-button>
          <!-- Root tree structure-->
          <b-button v-if="!parse" @click.stop="onFolderInfo({id:null})">
            <i class="fad fa-folder-tree pr-2"></i>
          </b-button>
        </b-button-group>
      </div>
    </div>

    <!-- search comp-->
    <search :bus="bus" :view="renderMode"></search>
    <!-- List view-->
    <div v-if="renderMode === 'list'">
      <media-table
          ref="mediatable"
          :api-url="url"
          :oninfo.prevent="onFolderInfo"
          :startFolder="startFolder"
          :editFunction="editItem"
          :onChange="onChange"
          :onDragEnd="dragEnd"
          :onDelete="deleteItem"
          :show="show"
          :parse="parse"
          :parseCallback="myParseCallback"
          :filetype="filetype"
          :bus="bus"
      ></media-table>
    </div>
    <!-- Icons view-->
    <div v-if="renderMode === 'icons'">
      <icons
          ref="mbicons"
          :url="url"
          :bus="this.bus"
          :parse="parse"
          :onpressed="updateBreadcrumb"
          :onedit="editItem"
          :ondeleteItem="deleteItem"
          :ontake="myParseCallback"
          :oninfo="onFolderInfo"
          :filetype="filetype"
          :startFolder="startFolder"
      ></icons>
    </div>
    <!-- Sidebar: iframe or tree comp-->
    <b-sidebar
        id="mediabase-bar"
        ref="sidebar"
        v-model="showSidebar"
        @shown="barIsShow"
        @hidden="barIsHidden"
        :title="siteBarTitle"
        :width="(parse)? '50%':'30%'"
        lazy right shadow backdrop>
      <iframe v-if="!infoActive" :class="frameClass" class="frame" :src="frameUrl" allowfullscreen></iframe>
      <ul v-if="infoActive" id="test">
        <tree v-if="infoActive" :current-folder-id="currentFolderId" :bus="this.bus"></tree>
      </ul>
    </b-sidebar>
    <!-- Modal: messages-->
    <b-modal
        v-model="showModalQuestion"
        title="Anfrage"
        ok-variant="success"
        cancel-variant="light"
        cancel-title="Abbruch"
        @ok="handleOk"
        modal-class="mb-modal-browser"
    >
      <div class="my-1 d-flex align-items-center">
        <i class="far fa-question-circle fa-2x pr-2"></i>
        <span v-html="question"></span>
      </div>
    </b-modal>
  </div>
</template>

<script>
import MediaTable from "./MediaTable";
import Icons from "./Icons";
import StoreMB from "./utils/store";
import Vue from "vue";
import Tree from "./utils/Components/Tree";
import Search from "./utils/Components/Search";
import axios from "axios";

export default {
  name: "media-browser",
  props: {
    apiUrl: String,
    startFolder: null,
    parse: false,
    parseCallback: Function,
    filetype: null,
    StoreMB,
  },
  components: {
    Search,
    Tree,
    Icons,
    MediaTable
  },
  data() {
    return {
      cookieKey: null,
      infoActive: false,
      currentFolderId: null,
      bus: new Vue(),
      items: [],
      haveParseMode: this.parse,
      renderMode: null,
      test: 'Hello',
      url: this.apiUrl,
      siteBarTitle: '',
      showSidebar: false,
      sidebarIsShown: false,
      frameUrl: '',
      frameLoaded: false,
      frameClass: '',
      currentItem: null,
      bitems: [],
      uploadbutton: false,
      showModalQuestion: false,
      question: '',
      questionObj: null,
      storeExist: true,
      searchWord: null,
    }
  },

  created() {
    //create the 'mediabaseBundleStore' if was not created
    if (this.$store && (!this.$store.hasModule("mediabaseBundleStore"))) {
      this.$store.registerModule("mediabaseBundleStore", StoreMB);
      //dispatch th bus to the store
      this.$store.dispatch("mediabaseBundleStore/dispatchBus", this.bus);
    }

    this.bus.$on('show-event', (data) => {
      this.show(data.data);
    });

    //reset the state of the data before the search
    this.bus.$on('search-cleared', (data) => {
      if (this.$refs.mediatable &&
          (this.$refs.mediatable.$refs.mbtable)
      ) {
        let url = this.getCurrentUrl;
        this.$refs.mediatable.$refs.mbtable.setApiUrlAndRefresh(url);
      }
    });

    //only applies in the case of the table
    this.bus.$on('search-event', (data) => {
      let url = ''
      //getSearchMinChars is used to decide when to fire the search ajax request(how many chars has the input field)
      if (data.data && this.renderMode === 'list' && (data.data.length >= this.getSearchMinChars)) {

        if (this.$refs.mediatable &&
            (this.$refs.mediatable.$refs.mbtable)
        ) {
          url = this.getSearchUrl + data.data;
          this.$refs.mediatable.$refs.mbtable.setApiUrlAndRefresh(url);
        }
      }
      if (this.$refs.mediatable &&
          (this.$refs.mediatable.$refs.mbtable)
      ) {
        if (data.data === '') {
          //return to the last data state
          url = this.getCurrentUrl;
          this.$refs.mediatable.$refs.mbtable.setApiUrlAndRefresh(url);
        }
      }
    });

    //check if the property 'parse' was passed and the search is
    //active clear the field and the search to clear the input field in case
    //the user close and reopens the sidebar window
    if (this.parse && this.searchActive) {
      this.$store.dispatch("mediabaseBundleStore/dispatchSearchActive", false);
    }


  },
  mounted() {

    //dispatch parse and startFolder if available
    if (this.parse && this.$store) {
      this.$store.dispatch("mediabaseBundleStore/dispatchParse", this.parse);
    }
    if (this.startFolder && this.$store) {
      this.$store.dispatch("mediabaseBundleStore/dispatchStartFolder", this.startFolder);
    }
    //clear the data if parse and startFolder in case that the user close and reopen the sidebar window
    if (this.parse && this.startFolder && this.$store) {
      this.$store.dispatch("mediabaseBundleStore/resetData");
    }

    if (!this.$store) {
      this.storeExist = false;
    }


    //read the cookie of the user if exist otherwise set one
    let url = '/Backend/MediaBase/getId'
    axios.get(url).then(response => {
      this.cookieKey = response.data.id;
      if (this.$cookies.isKey(this.cookieKey)) {
        this.renderMode = this.$cookies.get(this.cookieKey);
      } else {
        //when there is no cookie set default to list
        this.$cookies.set(this.cookieKey, 'list', '1m');
        this.renderMode = 'list';
      }
      //dispatch the renderMode
      if (this.$store) {
        this.$store.dispatch("mediabaseBundleStore/dispatchRenderMode", this.renderMode);
      }
    }).catch(e => {
    });
    //method for the events
    window.addEventListener("message", (event) => {
      switch (event.data.op) {
        case 'closeFrame':
          this.showSidebar = false;
          if (this.$refs.mediatable) {
            this.$refs.mediatable.refresh();
          } else if (this.$refs.mbicons) {
            this.refreshIcosn();
            //if the search is active load the changes
            if (this.$store) {
              if (this.searchActive) {
                this.$store.dispatch("mediabaseBundleStore/searchMedia");
              }
            }
          }

          break;
        case 'contentLoaded':
          this.frameLoaded = true;
          if (this.sidebarIsShown) {
            this.frameClass = "loaded";
          }
          break;
      }
    });

  },

  computed: {
    getSearchMinChars() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getParse"];
      }
    },
    getParse() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getParse"];
      }
    },
    getStartFolder() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getStartFolder"];
      }
    },
    searchActive() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getSearchActive"];
      }
    },
    getSearchUrl() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getUrlSearch"];
      }
    },
    getUploadButton() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getUploadButton"];
      }
    },
    getCurrentUrl() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getUrl"];
      }

    },
    isLoading() {
      if (this.$store) {
        return this.$store.getters["qstore/isLoading"];
      }

    },
    getBitems() {
      this.bitems = [{
        icon: 'fas fa-hdd',
        name: 'Root',
        mimeType: 'toParent'
      }];
      if (this.getData && (this.getData.tree && (this.getData.tree.length))) {
        this.getData.tree.forEach((item) => {
          this.bitems.push(item);
        });
      }

      return this.bitems;
    },
    getData() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getData"];
      }
    },
    getCurrentItem() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getCurrentItem"];
      }

    },
  },
  watch: {
    searchWord: function (new_value, old_value) {
      if (new_value && this.renderMode === 'list') {
        let url = ''
        if (new_value === '') {
          //return to the last data state
          url = this.getCurrentUrl;

        } else {
          url = this.getSearchUrl + this.searchWord;
        }

        //set the current url in store
        // this.$store.dispatch("mediabaseBundleStore/dispatchUrl", url);
        this.$refs.mediatable.$refs.mbtable.setApiUrlAndRefresh(url);
      }

    },
  },
  methods: {
    onFolderInfo: function (item) {
      this.currentFolderId = item.id
      this.infoActive = true;
      this.siteBarTitle = 'Folder Infos';
      this.showSidebar = true;
    },
    refreshIcosn: function () {
      if (this.$store) {
        this.$store.dispatch("mediabaseBundleStore/loadDataWithUrl", this.getCurrentUrl);
      }

    },
    changeView(view) {
      //to avoid errors in case that the store do not exist
      if (view === 'icons' && !this.$store) {
        return false;
      }

      //flag that the user has changed the views
      if (this.$store) {
        this.$store.dispatch("mediabaseBundleStore/dispatchViewWasChanged");
      }

      //save in the cookie user this configuration
      if (this.cookieKey) {
        if (this.$cookies.isKey(this.cookieKey)) {
          this.$cookies.set(this.cookieKey, view)
        }
      }
      //save the render mode in the store
      if (this.$store) {
        this.$store.dispatch("mediabaseBundleStore/dispatchRenderMode", view);
      }
      this.renderMode = view;
    },
    handleOk(bvModalEvt) {
      bvModalEvt.preventDefault();
      this.showModalQuestion = false;
      if (this.questionObj && (typeof this.questionObj.callback === 'function')) {
        this.questionObj.callback();
      }
      this.questionObj = null;
    },
    myParseCallback(item, icon) {
      if (this.parseCallback) {
        this.parseCallback(item, icon);
      }
    },
    dragEnd(obj) {
      this.questionObj = obj;
      this.question = '<b>' + obj.item.name + '</b> nach <b>' + ((obj.to.parent) ? (obj.to.name == '..') ? obj.to.parent.name : obj.to.name : 'Root') + '</b> verschieben ?';
      this.showModalQuestion = true
    },
    editItem(item) {
      if (item.isDirectory) {
        this.siteBarTitle = 'Ordner bearbeiten';
        this.frameUrl = this.url + '/EditFolder/' + item.id + ((item.parent) ? "/" + item.parent.id : "");
        this.showSidebar = true;
      } else {
        this.siteBarTitle = 'Datei bearbeiten';
        this.frameUrl = this.url + '/Upload/EditFile/' + item.id;
        this.showSidebar = true;
      }
    },
    newFile() {
      if (this.$refs.mediatable) {
        if (this.$refs.mediatable.currentItem) {
          this.siteBarTitle = 'Neue Datei anlegen in: ' + this.$refs.mediatable.currentItem.name;
          this.frameUrl = this.url + '/Upload/' + this.$refs.mediatable.currentItem.id;
          this.showSidebar = true;
        }
      } else if (this.$refs.mbicons) {
        if (this.getCurrentItem) {
          this.siteBarTitle = 'Neue Datei anlegen in: ' + this.getCurrentItem.name;
          this.frameUrl = this.url + '/Upload/' + this.getCurrentItem.id;
          this.showSidebar = true;
        }
      }
    },
    newFolder() {
      //media table exist:-> how to centralize data!! -> the store can do that?
      if (this.$refs.mediatable) {
        if (this.$refs.mediatable.currentItem) {
          this.siteBarTitle = 'Neuer Ordner in: ' + this.$refs.mediatable.currentItem.name;
          this.frameUrl = this.url + '/AddFolder/' + this.$refs.mediatable.currentItem.id;
          this.showSidebar = true;
        } else {
          this.siteBarTitle = 'Neuer Ordner in: Root';
          this.frameUrl = this.url + '/AddFolder';
          this.showSidebar = true;
        }
      } else if (this.$refs.mbicons) {
        let folder = this.getCurrentItem ? this.getCurrentItem : this.getStartFolder;
        if (folder) {
          this.siteBarTitle = 'Neuer Ordner in: ' + folder.name;
          this.frameUrl = this.url + '/AddFolder/' + folder.id;
          this.showSidebar = true;
        } else {
          this.siteBarTitle = 'Neuer Ordner in: Root';
          this.frameUrl = this.url + '/AddFolder';
          this.showSidebar = true;
        }
      }
    },
    deleteItem(obj) {
      this.questionObj = obj;
      this.question = '<b>' + obj.item.name + '</b> wirklich löschen ?';
      this.showModalQuestion = true;
    },
    show(item) {
      this.siteBarTitle = 'Details';
      this.frameClass = "";
      this.frameUrl = this.url + '/Details/' + item.id;
      this.showSidebar = true;
    },
    barIsShow() {
      this.sidebarIsShown = true;
      if (this.frameLoaded) {
        this.frameClass = "loaded";
      }
    },
    barIsHidden() {
      this.sidebarIsShown = false;
      this.frameClass = '';
      this.showSidebar = false;
      this.infoActive = false;
    },
    onChange(currentData) {
      this.items = currentData;
      if (this.$refs.mediatable && (this.$refs.mediatable.currentItem)) {
        this.currentItem = this.$refs.mediatable.currentItem;
        if (this.$refs.mediatable.currentItem) {
          this.uploadbutton = true;
        } else {
          this.uploadbutton = false;
        }
      }
      //this.$store.dispatch("mediabaseBundleStore/dispatchCurrentItem", this.currentItem);

      this.updateBreadcrumb(currentData);
    },
    updateBreadcrumb(currentData) {
      this.bitems = [{
        icon: 'fas fa-hdd',
        name: 'Root',
        mimeType: 'toParent'
      }];
      currentData.tree.forEach((item) => {
        this.bitems.push(item);
      });
    },
    breadcrumbClick(item) {
      //clear the search
      if (this.$store) {
        this.$store.dispatch("mediabaseBundleStore/dispatchSearchActive", false);
      }

      if (this.$refs.mediatable) {
        this.$refs.mediatable.clicked(item);
      } else {
        this.$refs.mbicons.clicked(item);
      }

    }
  }
}
</script>


<style scoped lang="scss">
@import '@core/scss/backend';

i.far,
i.fas {
  min-width: 15px;
}

.frame {
  width: calc(100% - 5px);
  height: calc(100% - 8px);
  border: 0px;
  opacity: 0;
  transition: opacity 100ms ease-in-out;
}

.frame.loaded {
  opacity: 1;
  transition: opacity 500ms ease-in-out;
}

.btn-group button:not(:first-child) {
  border-left: 1px solid darken($secondary, 10%);
}

div {

}

.mb-modal-browser {
  z-index: 10000 !important;
}
</style>