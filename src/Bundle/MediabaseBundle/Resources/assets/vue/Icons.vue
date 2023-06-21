<template>
  <!--Verify if the store exist 'storeExist' default true but is not the value is changed to false in 'mounted' event-->
  <div class="mb-icons-wrapper my-3" v-if="!isLoading && !dataEmpty && storeExist">
    <h3 v-if="!searchActive">Dokumente im Ordner: {{ folderName }}</h3>
    <h3 v-if="searchActive">Suchergebnisse: {{ searchWord }}</h3>
    <div class="d-flex flex-row flex-wrap justify-content-start position-relative mb-wrapper-elements my-1">
      <!--display no items-->
      <div v-if="showEmptyFolder"
           class="d-flex flex-column text-secondary align-items-center m-auto order-1">
        <i class="far fa-folder display-2"></i>
        <h3 v-if="!searchActive" class="text-secondary">Ordner ist leer</h3>
        <h3 v-if="searchActive" class="text-secondary">keine Suchergebnisse</h3>
      </div>

      <!--loop to display elements(items)-->
      <div v-for="item in getItems"
           :class="getElementClass(item)"
           class="mediabase-preview-wrapper p-1 mt-2 mb-3">

        <!-- back btn-->
        <div v-if="item.isDirectory && item.mimeType === 'toParent'"
             @click="clicked(item)"
             @drop="onDrop($event, item)"
             @dragenter.prevent
             @dragover.prevent
             draggable="false"
             class="d-flex flex-column align-items-center mb-elem"
             size="sm"
             title="Ordner hoch"
        >
          <i class="fad fa-folder-upload display-1 text-secondary"></i>
        </div>

        <!-- folders-->
        <div v-else-if="item.isDirectory && item.mimeType != 'toParent'"
             @click="clicked(item)"
             draggable="true"
             @drop="onDrop($event, item)"
             @dragenter.prevent
             @dragover.prevent
             @dragstart="startDrag($event,item)"
             @mouseleave="dropshowId = null"
             @mouseover="dropshowId = item.id"
             class="d-flex flex-column align-items-center mb-elem"
        >
          <div class="mb-icons-folder-wrapper">
            <i class="fa fa-folder display-1 text-primary"></i>
          </div>

          <div class="mb-elem-name m-0">
            <small class="mb-minus-1-margin-bottom" v-if="searchActive && item.filepath">{{ item.filepath }}</small>
            <p class="mb-p-icons-name" :title="item.name">{{ item.name }}</p>
          </div>
          <small v-if="searchActive && item.filepath">{{ item.filepath }}</small>
          <div v-if="!parse" class="mb-icons-adm-folder">
            <div v-if="dropshowId === item.id" class="mb-drop-cond">
              <dropdown
                  :onedit="onedit"
                  :item="item"
                  :folder="item"
                  :oninfo="oninfo"
                  :ondelete="deleteItem"></dropdown>
            </div>
          </div>
        </div>


        <!-- images-->
        <div
            @click.stop="clicked(item)"
            v-if="item.mimeType && item.mimeType.match(/image/)"
            class="d-flex flex-column mb-elem"
            draggable="true"
            @dragstart="startDrag($event,item)"
            @dragenter.prevent
            @dragover.prevent
            @mouseleave="dropshowId = null"
            @mouseover="dropshowId = item.id"
        >
          <div class="mb-icons-img-wrapper">
            <img class="mx-auto d-block"

                 :src="item.url +'/100x70'"/>
            <div class="mb-img-cover"></div>
          </div>
          <div class="mb-elem-name">
            <small class="mb-minus-1-margin-bottom" v-if="searchActive && item.filepath">{{ item.filepath }}</small>
            <p class="mb-p-icons-name" :title="item.name">{{ item.name }}</p>
          </div>

          <div v-if="!parse" class="mb-icons-adm">
            <div v-if="dropshowId === item.id" class="mb-drop-cond">
              <dropdown
                  :onedit="onedit"
                  :item="item"
                  :ondelete="deleteItem"
                  :bus="bus"
              ></dropdown>
            </div>
          </div>


          <div v-if="parse && !item.isDirectory" class="mb-select-elem">
            <b-button class="d-inline-block" v-if="item.mimeType!='toParent'" variant="primary" size="sm"
                      @click.stop="ontake(item, getItemExtension(item))">
              <i class="far fa-check-circle"></i> Wählen
            </b-button>
          </div>
        </div>


        <!-- PDF and ?? -->
        <div @click="clicked(item)" v-else-if="item.extension != '' && item.extension"
             @dragstart="startDrag($event,item)"
             draggable="true"
             class="d-flex flex-column align-items-center mb-elem"
             @mouseleave="dropshowId = null"
             @mouseover="dropshowId = item.id"
             @dragenter.prevent
             @dragover.prevent

        >
          <div class="mb-icons-pdf-wrapper">
            <i :class="getData.icons[item.extension]" class=" text-info display-3 mb-1"></i>
            <p class="position-absolute mb-ext-type text-info px-2">{{ item.extension }}</p>
          </div>
          <div class="mb-elem-name">
            <small class="mb-minus-1-margin-bottom" v-if="searchActive && item.filepath">{{ item.filepath }}</small>
            <p class="mb-p-icons-name" :title="item.name">{{ item.name }}</p>
          </div>

          <div v-if="!parse" class="mb-icons-adm">
            <div v-if="dropshowId === item.id" class="mb-drop-cond-pdf">
              <dropdown
                  :onedit="onedit"
                  :item="item"
                  :bus="bus"
                  :ondelete="deleteItem"></dropdown>
            </div>
          </div>

          <div v-if="parse && !item.isDirectory" class="mb-select-elem">
            <b-button class="d-inline-block" v-if="item.mimeType!='toParent'" variant="primary" size="sm"
                      @click="ontake(item, getItemExtension(item))">
              <i class="far fa-check-circle"></i> Wählen
            </b-button>
          </div>
        </div>
      </div>

    </div>
    <div class="d-flex justify-content-end mb-number-display">
      <small>Anzahl der Elemente: {{ getNumberOfDocuments }}</small>
    </div>
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

import axios from "axios";
import qs from 'qs';
import Dropdown from "./utils/Components/Dropdown";

export default {
  name: "Icons",
  components: {Dropdown},
  props: {
    parse: {
      type: Boolean,
    },
    onpressed: Function,
    onedit: Function,
    ondeleteItem: Function,
    ondrag: Function,
    ontake: Function,
    oninfo: Function,
    url: null,
    bus: null,
    filetype: null,
    startFolder: null,

  },
  data() {
    return {
      optParams: (this.filetype) ? 'filetype=' + this.filetype : '',
      items: [],
      question: '',
      showModalQuestion: false,
      questionObj: null,
      dropshowId: null,
      currentItem: null,
      //to verify if the store exist
      storeExist: true,
    }
  },
  mounted() {
    //DISPATCH THE OPTIONAL PARAMETERS
    //verify if the prop was passed 'filetype'
    if (this.filetype) {
      //dispatch the parameters
      if (this.$store) {
        this.$store.dispatch("mediabaseBundleStore/dispatchOptParameters", this.optParams);
      } else {
        this.storeExist = false;
      }
    }

    //CHECK IF THE PARSE AND THE START FOLDER(currentItem) WERE SET
    //THIS MAKE THE DIFFERENCE BETWEEN THE SELECT MEDIA AND MANAGE MEDIA FUNCTIONS(AREAS)
    if (this.getParse && this.getStartFolder) {
      this.currentItem = this.getStartFolder;
      //not apply when the search is active and only if there is no data available
      if (!this.searchActive && !this.getData) {
        //let url = this.getCurrentUrl;
        let url = '/Backend/MediaBase';
        this.$store.dispatch("mediabaseBundleStore/loadDataWithUrl", url + '/' + this.currentItem.id);
      }
    } else {
      //MANAGE AREA
      //check the store to verify if data available
      if (!this.getData) {
        //load the data
        this.clicked({mimeType: "toParent"})
      }
    }
  },
  created() {
    this.bus.$on('goto-event', (data) => {
      this.clicked(data.data);
    });
  },
  computed: {
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
    searchWord() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getSearchWord"];
      }

    },
    showEmptyFolder() {
      return this.getNumberOfDocuments < 1;
    },
    getNumberOfDocuments() {
      let numberOfDocs = 0;
      if (this.getItems && (this.getItems.length)) {
        for (let i = 0; i < this.getItems.length; i++) {
          if (this.getItems[i].isDirectory && this.getItems[i].mimeType === 'toParent') {
            continue;
          }
          numberOfDocs++;
        }
      }

      return numberOfDocs;
    },
    dataEmpty() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getData"].empty
      }
    },
    isLoading() {
      //get the data only if it was set
      //'empty' property in the data.empty is to flag that there is no data available
      if (this.$store) {
        if (!this.$store.getters["mediabaseBundleStore/getData"].empty) {
          return this.$store.getters["qstore/isLoading"];
        }
      }
    },
    folderName() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getCurrentFolder"];
      }
    },
    getData() {
      if (this.$store) {
        if (!this.$store.getters["mediabaseBundleStore/getData"].empty) {
          return this.$store.getters["mediabaseBundleStore/getData"];
        }
      }
    },
    getItems() {
      if (this.$store) {
        this.items = this.getData.items;
      }
      return this.items;
    },
    getCurrentUrl() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getUrl"];
      }
    },
  },
  methods: {

    isVertical: function (item) {
      let toReturn = false;
      if (item.metas &&
          (item.metas.length > 1 &&
              (item.metas[0].name === 'height' && item.metas[1].name === 'width'))) {
        let height = item.metas[0].value;
        let width = item.metas[1].value;

        if (+height > +width) {
          toReturn = true;
        }

      }
      return toReturn;
    },
    setDropshowID(id) {
      this.dropshowId = id;
    },
    getItemExtension: function (item) {
      let toReturn = '';
      if (item.extension) {
        if (this.getData && (this.getData.icons)) {
          toReturn = this.getData.icons[item.extension]
        }
      }
      return toReturn;
    },
    getElementClass: function (item) {
      let toReturn = '';
      // item.isDirectory && item.mimeType != 'toParent'?'order-1':'order-4'
      if (item.isDirectory && item.mimeType != 'toParent') {
        toReturn = 'order-1 p-1 m-2';
      } else if (item.isDirectory && item.mimeType === 'toParent') {
        toReturn = 'order-0'
      } else {
        toReturn = 'order-4'
      }
      return toReturn;
    },
    handleOk(bvModalEvt) {
      // Prevent modal from closing
      bvModalEvt.preventDefault();
      this.showModalQuestion = false;
      if (this.questionObj && (typeof this.questionObj.callback === 'function')) {
        this.questionObj.callback();
      }
      this.questionObj = null;
    },
    startDrag: function (event, item) {
      if (this.searchActive) {
        return false;
      }
      //to storage the item been dragged
      event.dataTransfer.dropEffect = 'move';
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('itemDraggedName', item.name);
      event.dataTransfer.setData('itemDraggedId', item.id);
      if (item.parent) {
        event.dataTransfer.setData('itemDraggedParent', item.parent.id);
        event.dataTransfer.setData('itemDraggedParentName', item.parent.name);
      }


    },
    refreshIcosn: function () {
      if (this.$store) {
        this.$store.dispatch("mediabaseBundleStore/loadDataWithUrl", this.getCurrentUrl);
      }
    },
    onDrop(event, item) {
      if (this.searchActive) {
        return false;
      }

      let itemDraggedName = event.dataTransfer.getData('itemDraggedName');
      let itemDraggedId = event.dataTransfer.getData('itemDraggedId');
      let itemDraggedParent = event.dataTransfer.getData('itemDraggedParent');
      let itemDraggedParentName = event.dataTransfer.getData('itemDraggedParentName');

      if (!itemDraggedName && !itemDraggedId) {
        return false;
      }


      //return if is the same folder
      if (itemDraggedId == item.id
      ) {
        return false;
      }
      //name of the folder to drop into.
      let dropIntoName = item.name
      if (item.name === '..') {
        if (item.parent) {
          dropIntoName = item.parent.name;
        } else {
          dropIntoName = 'Root';
        }

      }

      let idOfParent = item.id;
      //check if drop into item has no Parent-> inside the root folder, and also if
      //item dragged has a parent-> inside other folder
      if (!item.parent && itemDraggedParent) {
        idOfParent = null;
      }

      this.question = 'Element move?: <b>' + itemDraggedName + '</b> to: <b>' + dropIntoName + '</b>';
      this.questionObj = itemDraggedName;
      this.showModalQuestion = true;


      // check if the elem into drop is the back btn(folder)
      if (item.mimeType === "toParent" && item.name === '..') {
        //check if it has a parent if not is the root and there are only folders allowed.
        if (item.parent) {
          idOfParent = item.parent.id;
        }

      }
      if (this.showModalQuestion) {
        this.questionObj = ({
          item: item,
          to: idOfParent,
          callback: () => {
            this.callAndRefresh({
              action: 'move',
              id: itemDraggedId,
              to: idOfParent
            });
          }
        });
      } else {
        this.callAndRefresh({
          action: 'move',
          id: itemDraggedId,
          to: idOfParent
        });
      }
    },
    deleteItem(item) {
      if (this.ondeleteItem) {
        this.ondeleteItem({
          item: item,
          callback: () => {
            this.callAndRefresh({
              action: 'delete',
              id: item.id
            });
          }
        });
      } else {
        this.callAndRefresh({
          action: 'delete',
          id: item.id
        });
      }
    },
    callAndRefresh(data, url = null) {
      let self = this;
      let url_ = this.getCurrentUrl;
      let promise = axios.post(url_, qs.stringify(data), {
        headers: {
          'Content-Type':
              'application/x-www-form-urlencoded'
        }
      });
      promise.then((data) => {
        self.refreshIcosn();
        //if the search is active load the changes
        if (self.$store) {
          if (self.searchActive) {
            self.$store.dispatch("mediabaseBundleStore/searchMedia");
          }
        }
      }).catch(error => {
        self.refreshIcosn();
      });
    },
    getImgStyle: function (url) {
      return "background: url(" + url + "/100x100);background-size: cover;";
    },
    clicked(item, index) {
      //set to null the dropshowId
      this.dropshowId = null;

      if (item.mimeType === "toParent") {

        if (item.parent) {
          if (this.$store) {
            this.$store.dispatch("mediabaseBundleStore/dispatchCurrentItem", item.parent);
            this.$store.dispatch("mediabaseBundleStore/loadDataWithUrl", this.url + '/' + item.parent.id)
          }
        } else {
          if (this.$store) {
            //set to null the start folder to allow the navigation switch
            if (this.getParse) {
              this.$store.dispatch("mediabaseBundleStore/dispatchStartFolder", null);
            }
            this.$store.dispatch("mediabaseBundleStore/dispatchCurrentItem", null);
            this.$store.dispatch("mediabaseBundleStore/loadDataWithUrl", this.url)
          }
        }
      } else if (item.isDirectory) {
        if (this.$store) {
          this.$store.dispatch("mediabaseBundleStore/dispatchSearchActive", false);
          this.$store.dispatch("mediabaseBundleStore/dispatchCurrentItem", item);
          this.$store.dispatch("mediabaseBundleStore/loadDataWithUrl", this.url + '/' + item.id)
        }
      } else {
        try {
          this.bus.$emit('show-event', {data: item, op: 'media-details'});
        } catch (e) {
        }
      }

    },
  }
}
</script>

<style lang="scss">
ul.dropdown-menu.mb-adm-btns.show {
  max-width: 50px !important;
  padding: 0 !important;
}

.mb-modal-browser {
  z-index: 10000 !important;
}

.mb-icons-wrapper {
  margin-top: 50px;
  position: relative;
  min-height: 300px;

  .mb-wrapper-elements {
    min-height: 300px;
  }

  //folders(icons)
  i {
    line-height: .8em;
  }

  .mb-number-display {
    position: absolute;
    bottom: -40px;
    right: 10px;
  }

  .mb-elem {
    position: relative;
    height: 115px;
    width: 120px;
    cursor: pointer;

    &:hover {
      opacity: .8;
    }

    .mb-select-elem {
      position: absolute;
      top: 0;
    }

    .mb-elem-name {
      height: 3em;
      background: #FFFFFF;

      .mb-minus-1-margin-bottom {
        margin-bottom: -5px;
      }
    }

    .mb-p-icons-name{
      line-height: normal;
    }
    .mb-ext-type {
      top: 0;
      left: 0;
      width: 100%;
      text-align: center;
      background: white;
    }

    img {
      max-width: 100%;
    }

    p, small {
      text-align: center;
      word-break: break-all;
      display: -webkit-box !important;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      white-space: normal;
      overflow: hidden;
      text-overflow: ellipsis !important;
    }

    .mb-icons-adm-folder {
      position: absolute;
      top: 2px;
      left: 12px;
      // max-width: 50px !important;
      min-width: 80%;
    }
    .mb-icons-adm {
      position: absolute;
      top: 0;
      left: 10px;
      // max-width: 50px !important;
      min-width: 80%;
    }
    .mb-drop-cond-pdf {
      position: absolute;
      top: -8px;
      left: 0px;
      // max-width: 50px !important;
      min-width: 80%;
    }
  }

  .md-back-btn {
    p {
      margin: 0;
      font-size: 1.2em;

      i {
        &:nth-child(2) {
          font-size: 1.4em
        }
      }
    }
  }

  .mb-icons-img-wrapper, .mb-icons-pdf-wrapper, .mb-icons-folder-wrapper {
    position: relative;
    min-height: 80px;

    .mb-img-cover {
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0;
      top: 0;
    }
  }

}


</style>