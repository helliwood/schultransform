<template>
  <div class="mediaTable pb-5 mb-5">
    <h4 class="m-3" v-if="getSearchActive">
      <span v-if="getSearchedWord">Suchergebnisse: {{ getSearchedWord }}</span>
    </h4>

    <!--        <div class="filterContainer d-flex align-items-center just">
              <i class="fas fa-search"></i>
              <b-form-input
                  v-model="filter"
                  type="search"
                  id="filterInput"
                  placeholder="Filter...">
              </b-form-input>
            </div>-->
    <data-table
        ref="mbtable"
        :apiUrl="url"
        :apiAppendParams="optParams"
        :sort-by.sync="sortBy"
        :sort-desc.sync="sortDesc"
        sort-by="order"
        sort-icon-left
        show-empty
        small
        v-sortable="items"
        @refreshed="refreshed"
        :filterIncludedFields="filterOn"
        :no-provider-sorting="false"
        selectable
        select-mode="single"
        :rowClicked="clicked"
        :sort-desc="false"
        :per-page="40"
        :fields="getFields"
        @filtered="onFiltered"
        :filter="filter"
        :filter-included-fields="filterOn"
    >
      <!--Define the width of the cell columns-->
      <template #table-colgroup="scope">
        <col
            v-for="field in scope.fields"
            :key="field.key"
            :style="colStyle(field)"
        >
      </template>

      <!--Actions buttons-->
      <template v-slot:cell(options)="{ row, callAndRefresh }">
        <div v-if="!parse">
          <!--Edit-->
          <b-button class="d-none d-xl-inline-block" v-if="row.item.mimeType!='toParent'" variant="primary" size="sm"
                    @click="edit(row.item)">
            <i class="far fa-edit"></i> Bearbeiten
          </b-button>
          <!--Delete-->
          <b-button class="d-none d-xl-inline-block"
                    v-if="row.item.mimeType!='toParent'&&(!getSearchActive || (getSearchActive && !row.item.isDirectory))"
                    variant="danger" size="sm"
                    @click="deleteItem(row.item)">
            <i class="fas fa-trash-alt"></i> Löschen
          </b-button>
          <!--Info-->
          <b-button class="d-none d-xl-inline-block" v-if="!parse && row.item.isDirectory && row.item.name !== '..'"
                    variant="info" size="sm"
                    @click="oninfo(row.item)">
            <i class="fas fa-info"></i> Info
          </b-button>
          <!--Go to directory if search is active-->
          <b-button v-if="!parse && getSearchActive && !row.item.isDirectory"
                    class="d-none d-xl-inline-block"
                    @click.stop="goToDir(row.item)" size="sm">
            <i class="fa fa-folder-open pt-1"></i>
            <span>Zum Ordner</span>
          </b-button>

          <!--Responsive btns-->
          <b-dropdown v-if="row.item.name !== '..'" text="Bearbeiten" class="d-xl-none" size="sm"
                      variant="primary">
            <b-dropdown-item variant="primary" @click="edit(row.item)"><i class="far fa-edit"></i> Bearbeiten
            </b-dropdown-item>
            <!--Show info directory-->
            <b-dropdown-item v-if="!parse && row.item.isDirectory" variant="primary" @click="oninfo(row.item)">
              <i class="fas fa-info"></i> Info
            </b-dropdown-item>
            <!--Go to directory if search is active-->
            <b-dropdown-item v-if="!parse && getSearchActive && !row.item.isDirectory" variant="primary"
                             @click="goToDir(row.item)">
              <i class="fa fa-folder-open"></i> Zum Ordner
            </b-dropdown-item>
            <!--Delete-->
            <b-dropdown-item variant="danger"
                             v-if="!getSearchActive || (getSearchActive && !row.item.isDirectory)"
                             @click="deleteItem(row.item)"><i class="fas fa-trash-alt"></i> Löschen
            </b-dropdown-item>
          </b-dropdown>
        </div>
        <div v-if="parse && !row.item.isDirectory">
          <b-button class="d-inline-block" v-if="row.item.mimeType!='toParent'" variant="primary" size="sm"
                    @click="take(row.item)">
            <i class="far fa-check-circle"></i> Wählen
          </b-button>
        </div>


      </template>

      <!--Image in 'id' row-->
      <template v-slot:cell(id)="{ row, callAndRefresh }">
        <div class="mediabase-preview-wrapper d-flex flex-row justify-content-center align-items-center">
          <div v-if="row.item.mimeType && row.item.mimeType.match(/image/)"
               class="mb-image-row-wrapper  d-flex flex-row justify-content-center align-items-center">
            <img class="img-fluid"
                 :src="row.item.url + '/40x40'"
            >
          </div>

          <i v-else-if="row.item.isDirectory && row.item.mimeType != 'toParent'"
             class="fa fa-folder mb-column-icon-size text-primary"></i>
          <i v-else-if="row.item.extension != ''" :class="$refs.mbtable.currentData.icons[row.item.extension]"
             class="mb-column-icon-size text-primary"></i>
          <img :id="'mb-img-row-'+row.item.id" v-if="row.item.mimeType && row.item.mimeType.match(/image/)"
               class="img-fluid mediabase-preview-image"
               :src="row.item.url+'/100x90' " @click="preview(row.item,$event)"/>

        </div>
      </template>

      <template v-slot:cell(mimeType)="{ row, callAndRefresh }">
        <span v-if="row.item.mimeType!='toParent'">{{ row.item.mimeType }}</span>
      </template>

      <template v-slot:cell(formatFileSize)="{ row, callAndRefresh }">
        <span v-if="row.item.mimeType!='toParent' && !row.item.isDirectory">{{ row.item.formatFileSize }}</span>
      </template>

      <!-- Override the column head 'mimeType' and add here the extension-->
      <template v-slot:cell(mimeType)="{ row, callAndRefresh }">
        <div v-if="row.item.mimeType!='toParent'" class="d-flex flex-column justify-content-center">
          <p class="p-0 m-0">{{ row.item.mimeType }}</p>
          <small v-if="row.item.extension">Endung: {{ row.item.extension }}</small>
        </div>

      </template>
      <template v-slot:cell(name)="{ row, callAndRefresh }">
        <div class="d-flex flex-column justify-content-center">
          <small v-if="getSearchActive && row.item.filepath">{{ row.item.filepath }}</small>
          <p class="m-0 p-0">{{ row.item.name }}</p>
        </div>

      </template>
    </data-table>
    <div id="mb-test"></div>
  </div>
</template>


<script>
import Sortable from "sortablejs";
import axios from 'axios'
import qs from 'qs';

export default {
  name: "media-table",
  props: {
    apiUrl: String,
    parse: Boolean,
    parseCallback: Function,
    filetype: null,
    editFunction: Function,
    onChange: Function,
    show: Function,
    onDragEnd: Function,
    onDelete: Function,
    startFolder: null,
    bus: null,
    oninfo: Function,
  },
  data() {
    return {
      url: this.apiUrl,
      optParams: (this.filetype) ? 'filetype=' + this.filetype : '',
      currentTargetItem: null,
      currentItem: null,
      sortBy: 'name',
      sortDesc: true,
      sortDirection: 'asc',
      totalRows: 1,
      currentPage: 1,
      filter: null,
      filterOn: [],
      items: [],
      sortableOptions: {
        chosenClass: 'is-selected'
      },
    }
  },
  directives: {
    sortable: {
      bind(el, binding, vnode) {
        let self = el;
        Sortable.create(el.querySelector('tbody'), {
          ...binding.value,
          vnode: vnode,
          toNode: null,
          onEnd: (e) => {
            binding.value = []
            vnode.context.items = []
            if (self.toNode) {
              self.toNode.removeAttribute("data-class");
              vnode.componentInstance.$parent.currentTargetItem = self.toNode;
              vnode.componentInstance.$parent.dragEnd(e.item);
            }
          },
          onSort: (e) => {
            return false;
          },
          onMove: (e) => {


            e.related.parentElement.querySelectorAll('tr').forEach(node => {
              node.removeAttribute("data-class");
            })
            if (e.related.getAttribute("class") != "no-drop") {
              e.related.setAttribute("data-class", "sortable-swap-highlight");
              self.toNode = e.related;
            } else {
              self.toNode = null;
            }
            return false;
          },
          swap: true,
          invertSwap: true,
          filter: ".no-drag"
        });
      },
    }
  },
  created() {
    //if the list view is active only
    this.bus.$on('goto-event', (data) => {
      if (data.renderMode === 'list') {
        this.clicked(data.data);
      }
    });

  },
  mounted() {
    //SET THE 'optParams': for the correct function of the search function
    if (this.optParams) {
      this.$store.dispatch("mediabaseBundleStore/dispatchOptParameters", this.optParams);
    }

    //SET THE CURRENT ITEM: original currentItem is null but its value is updated when the user
    //clicks the folders. Set the value of the start folder if exist
    if (this.getStartFolder) {
      this.currentItem = this.getCurrentItem ? this.getCurrentItem : this.getStartFolder;
    } else {
      this.currentItem = this.getCurrentItem;
    }


    //IF THE SEARCH IS NOT ACTIVE LOAD THE FOLDER CONTAINING THE MEDIA
    if (!this.searchActive && this.currentItem) {
      this.$refs.mbtable.setApiUrlAndRefresh(this.url + '/' + this.currentItem.id);
    }

    if (this.getSearchActive) {
      //if the view is changed while search active ->
      // show the same results in the table
      let url = this.getUrlSearch;
      let wordSearched = this.getSearchedWord;
      this.$refs.mbtable.setApiUrlAndRefresh(url + wordSearched);
    }

  },
  computed: {
    getCurrentItem() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getCurrentItem"];
      }
    },
    getViewWasChanged() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getViewWasChanged"];
      }
    },
    getData() {
      if (this.$store) {
        if (!this.$store.getters["mediabaseBundleStore/getData"].empty) {
          return this.$store.getters["mediabaseBundleStore/getData"];
        }
      }
    },
    getUrlSearch() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getUrlSearch"];
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
    getFields() {
      let toReturn = [
        {key: 'id', label: "", sortable: false},
        {key: 'name', label: "Dateiname", sortable: true},
        {key: 'mimeType', label: "Type", sortable: true},
        // to make space and avoid scroll ability {key: 'extension', label: "Endung", sortable: true},
        {key: 'formatFileSize', label: "Größe", sortable: true},
        {key: 'options', label: "Aktionen", variant: 'secondary'}
      ];
      if (this.getSearchActive) {
        toReturn = [
          {key: 'id', label: "", sortable: false},
          {key: 'name', label: "Dateiname", sortable: true},
          //  {key: 'mimeType', label: "Type", sortable: true},
          // {key: 'extension', label: "Endung", sortable: true},
          //  {key: 'formatFileSize', label: "Größe", sortable: true},
          {key: 'options', label: "Aktionen", variant: 'secondary'}
        ];
      }
      return toReturn;
    },
    getRenderMode() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getRenderMode"];
      }
    },
    getSearchActive() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getSearchActive"];
      }
    },
    getSearchedWord() {
      if (this.$store) {
        return this.$store.getters["mediabaseBundleStore/getSearchWord"];
      }
    },

  },
  methods: {
    /*Defining the style-width for the columns*/
    colStyle: function (field) {
      if (!field.key) {
        return false;
      }
      let toReturn = '';
      switch (field.key) {
        case 'id':
          toReturn = 'width:10%;';
          break;
        case 'options':
          toReturn = 'width:30%;';
          break;
        case 'name':
          toReturn = 'width:30%;';
          break;
        default:
          toReturn = 'width:12%;';
          break;
      }

      return toReturn;

    },

    /*Mouse events for the image row*/
    imgRowMouseover: function (item, row) {
      //check the index and the number of items

      if (row) {
        if ((row.index + 2 >= this.items.length) || this.items.length < 2) {
          //make the position top change
          let imgHeight = '0';
          let imgWidth = '0';
          //check if horizontal or vertical picture
          if (item && (item.metas)) {
            if (item.metas[0].name && (item.metas[0].name === 'height')) {
              imgHeight = item.metas[0].value;
              imgWidth = item.metas[1].value;
              //if is the last or one before ~=

              //calculate the height
              let percentScale = (100 * 90) / imgWidth;
              //height of the row image = 40
              let total = ((percentScale * imgHeight) / 100) - 40;

              total = Math.round(total);
              total = Math.abs(total);
              //border size 1 px
              total = total - 1;
              imgHeight = '-' + total + 'px';
            }
          }

          let imgId = 'mb-img-row-' + item.id;
          //if is the last or one before change the top position
          let hiddeElement = document.getElementById(imgId);
          hiddeElement.style.top = imgHeight;
        }


      }

    },

    /* Inter method to sanitize data before clicked*/
    goToDir: function (item) {

      if (item &&
          (item.parent && (item.parent.id))
      ) {
        let data = {
          mimeType: 'toParent',
          parent: {
            id: item.parent.id,
          }
        }

        this.clicked(data);

      }
    },

    clicked(item, index) {

      //if it exists
      if (!this.$refs.mbtable) {
        return false;
      }
      //set to false the search
      //this.$store.dispatch("mediabaseBundleStore/dispatchSearchActive", false);


      if (item.mimeType == "toParent") {

        if (item.parent) {
          this.currentItem = item.parent;
          if (this.$store) {
            this.$store.dispatch("mediabaseBundleStore/dispatchCurrentItem", this.currentItem);
            this.$store.dispatch("mediabaseBundleStore/dispatchUrl", this.url + '/' + item.parent.id);
            this.$store.dispatch("mediabaseBundleStore/dispatchSearchActive", false);
          }

          this.$refs.mbtable.setApiUrlAndRefresh(this.url + '/' + item.parent.id);
        } else {
          this.currentItem = null;
          if (this.$store) {
            this.$store.dispatch("mediabaseBundleStore/dispatchCurrentItem", this.currentItem);
            this.$store.dispatch("mediabaseBundleStore/dispatchUrl", this.url);
          }
          this.$refs.mbtable.setApiUrlAndRefresh(this.url);
        }
      } else if (item.isDirectory) {
        this.currentItem = item;
        if (this.$store) {
          this.$store.dispatch("mediabaseBundleStore/dispatchSearchActive", false);
          this.$store.dispatch("mediabaseBundleStore/dispatchCurrentItem", this.currentItem);
          this.$store.dispatch("mediabaseBundleStore/dispatchUrl", this.url + '/' + item.id)
        }

        this.$refs.mbtable.setApiUrlAndRefresh(this.url + '/' + item.id);
      } else {
        try {
          this.show(item);
        } catch (e) {
        }
      }
    },
    refreshed: function () {
      this.$nextTick(function () {
        this.items = this.$refs.mbtable.$refs.table.sortedItems;

        //fill out the store
        if (this.$store && !this.$store.getters["mediabaseBundleStore/getSearchActive"]) {
          this.$store.dispatch("mediabaseBundleStore/dispatchCurrentData", this.$refs.mbtable.currentData);
        }

        var n = 0;
        this.$el.querySelectorAll("tbody tr").forEach(node => {
          node.setAttribute("data-index", n);
          try {
            if (this.items[n] && !this.items[n].isDirectory) {
              node.setAttribute("class", "no-drop");
            } else if (node.childNodes && (node.childNodes[1] &&
                (node.childNodes[1].firstChild &&
                    (node.childNodes[1].firstChild.firstChild
                        && (node.childNodes[1].firstChild.firstChild.data
                            && (node.childNodes[1].firstChild.firstChild.data == "..")))))) {
              node.setAttribute("class", "no-drag");
            } else {
              node.setAttribute("class", "");
            }
          } catch (e) {
            console.error(e);
          }
          n++;
        });
        try {
          this.onChange(this.$refs.mbtable.currentData);
        } catch (e) {
        }
      });
    },
    updated() {
      // console.log("updated");
    },
    dragEnd(item) {
      //do not fire if the search is active
      if (this.getSearchActive) {
        return false;
      }

      const stack = this.$refs.mbtable.$refs.table.sortedItems;
      item = stack[item.getAttribute("data-index")];
      const index = this.currentTargetItem.getAttribute("data-index");
      const to = stack[index];
      // console.log(this.currentTargetItem);
      if (this.onDragEnd) {
        this.onDragEnd({
          item: item, to: to, callback: () => {
            this.callAndRefresh({
              action: 'move',
              id: item.id,
              to: (to.name == '..') ? ((to.parent) ? to.parent.id : to.parent) : to.id
            });
          }
        });
      } else {
        this.callAndRefresh({
          action: 'move',
          id: item.id,
          to: (to.name == '..') ? ((to.parent) ? to.parent.id : to.parent) : to.id
        });
      }
    },
    start(e) {
      // console.log('start');
    },
    end(e) {
      // console.log('end');
    },
    edit(item) {
      if (this.editFunction)
        this.editFunction(item);
    },
    take(item) {
      if (this.parseCallback) {
        this.parseCallback(item, this.$refs.mbtable.currentData.icons[item.extension]);
      }
    },
    deleteItem(item) {
      if (this.onDelete) {
        this.onDelete({
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
    log() {
      console.table(this.items)
    },
    callAndRefresh(data, url = null) {
      var self = this;

      this.$refs.mbtable.$refs.table.isBusy = true;
      let promise = axios.post(url ? url : (this.apiUrl ? this.apiUrl : ''), qs.stringify(data), {
        headers: {
          'Content-Type':
              'application/x-www-form-urlencoded'
        }
      });
      promise.then((data) => {
        self.refresh();
      }).catch(error => {
        self.refresh();
      });
    },
    refresh() {
      var self = this;
      self.$refs.mbtable.$refs.table.isBusy = false;
      self.$refs.mbtable.$refs.table.$nextTick(function () {
        self.$refs.mbtable.$refs.table.refresh();
      });
    },
    onFiltered(filteredItems) {
      // Trigger pagination to update the number of buttons/pages due to filtering
      this.$refs.mbtable.$refs.table.totalRows = filteredItems.length
      this.$refs.mbtable.$refs.table.currentPage = 1
    },
    preview(item, event) {
      if (event) {
        event.preventDefault();
        event.stopPropagation();
        window.open(item.url, '_blank').focus();
      }

    }

  }
};
</script>

<style lang="scss">
@import '@core/scss/backend';

.mediaTable {
  overflow: visible;

  .preview-Image {
    width: 50px;
  }

  #my-table {
    td {
      vertical-align: middle;
      padding: 1px !important;
    }
  }

  #filterInput {
    border: 0px;
  }

  i.far,
  i.fas {
    min-width: 15px;
  }

  td[aria-colindex="6"],
  th[aria-colindex="6"] {
    text-align: right;
    max-width: 100px;
    min-width: 50px;
    white-space: nowrap;
  }

  .filterContainer {
    border-top: 1px solid #dee2e6;
  }

  tr[data-class='sortable-swap-highlight'],
  .sortable-swap-highlight {
    background-color: $success !important;
  }

  tr:focus,
  .b-table-row-selected.table-active,
  .b-table-row-selected.table-active:focus {
    outline: none !important;
    outline-width: 0px;
  }


  .mediabase-preview-wrapper {

    display: inline-block;
    position: relative;

    .mb-image-row-wrapper {
      height: 40px;
      overflow: hidden;
    }

    .mb-column-icon-size {
      font-size: 1.6em;
    }

    .mediabase-preview-image {
      display: none;
      position: absolute;
      border: 1px solid #FFFFFF;
      width: 90px;
      max-width: 80vw;
      z-index: 99999999;
      left: 74%;
      top: -1px
    }

    &:hover .mediabase-preview-image {
      display: block;
    }
  }


}
</style>
