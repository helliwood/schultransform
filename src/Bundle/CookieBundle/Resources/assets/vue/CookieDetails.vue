<template>
  <div class="coo-details-wrapper">
    <div v-if="dataParent">
      <h4 class="m-0">{{ dataParent.name }}</h4>
      <div class="my-3">
        <b-button variant="secondary" size="sm" @click="loadEditTemplateText"><i class="fa fa-edit"></i>
          Texte und Einstellungen ändern
        </b-button>
      </div>
    </div>

    <div class="d-flex justify-content-end my-4">
      <b-button variant="primary" @click="loadCreateTemplate">Neues Item
      </b-button>
    </div>
    <div>
      <h3 class="mb-4">Gespeicherte Items / Themen:</h3>
    </div>
    <!--Table-->
    <b-table
        ref="cotable"
        :fields="fields"
        show-empty
        empty-text="Keine Daten vorhanden"
        :items="items"
        :per-page="itemsPerPage"
        :current-page="currentPage"
        :sort-icon-left="true"
        class="coo-table-details"
        responsive
    >
      <template #table-colgroup="scope">
        <col
            v-for="field in scope.fields"
            :key="field.key"
            :style="{ width: field.key === 'name' ? '50%' :''}"
        >
      </template>
      <template v-slot:cell(name)="row">
        <h5 draggable="true"
            @dragstart="dragStart(row.item)"
            @dragover.prevent
            @dragenter="dragEnter(row.item)"
            @drop="dragDrop(row.item)"
            @dragleave="dragLeave(row.item)"
            class="py-2 px-5 coo-cursor-move"
            :class="itemClass === row.item.id?' bg-primary text-white':''"
        >{{ row.item.name }}</h5>
      </template>
      <template v-slot:cell(actions)="row">
        <a @click="showCookie(row.item)"
           class="btn btn-sm btn-primary mr-1 d-none d-xl-inline-block"
           size="sm" target="_blank">
          <i class="fas fa-eye"></i> Anzeigen
        </a>
        <a size="sm"
           @click="loadEditTemplateCookies(row.item)"
           class="btn btn-sm btn-primary mr-1 d-none d-xl-inline-block">
          <i class="far fa-edit"></i> Bearbeiten
        </a>
        <a size="sm"
           @click="deleteConfirmation(row.item)"
           class="btn btn-sm btn-danger mr-1 d-none d-xl-inline-block">
          <i class="fas fa-trash-alt"></i>
        </a>


        <b-dropdown id="dropdown-left" text="Bearbeiten" class="d-xl-none" size="sm" variant="primary">
          <!--Show cookie action-->
          <b-dropdown-item variant="primary" @click="showCookie(row.item)">
            <i class="fas fa-eye"></i> Anzeigen
          </b-dropdown-item>

          <!--Edit action-->
          <b-dropdown-item variant="primary" @click="loadEditTemplateCookies(row.item)">
            <i class="far fa-edit"></i> Bearbeiten
          </b-dropdown-item>

          <!--Delete action-->
          <b-dropdown-item variant="primary" @click="deleteConfirmation(row.item)">
            <i class="fas fa-trash-alt text-danger"></i> <span class="text-danger">{{ deleteText }}</span>
          </b-dropdown-item>
        </b-dropdown>


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

    <!--Side bar-->
    <b-sidebar
        :class="'coo-overflow-hidden'"
        :title="sideTitle"
        @hidden="hiddenSideBar"
        @shown="showSideBar"
        v-model="sideBar"
        id="co-bar"
        ref="co_sidebar"
        :width="'50%'"
        lazy right shadow backdrop>
      <iframe
          ref="co_iframe"
          class="h-100 w-100 coo-iframe"
          :src="iframeSrc"
          allowfullscreen
      >
      </iframe>
    </b-sidebar>

    <!--Modal window-->
    <b-modal
        v-model="modal"
        hide-header
        id="modal-prevent-closing"
        ref="modal"
        title="Submit Your Name"
        hide-footer
    >
      <template v-slot:modal-header></template>
      <settings :data="itemToShow"></settings>
    </b-modal>
  </div>
</template>

<script>
import axios from "axios";
import qs from 'qs';
import Item from "./visualization/Item";
import Settings from "./visualization/Settings";

export default {
  name: "CookieDetails",
  components: {Settings, Item},
  props: {
    itemsPerPage: 5,
    appUrl: null,
    recordId: null
  },
  data() {
    return {
      modal: false,
      itemToShow: null,
      itemClass: 0,
      itemDragged: null,
      dataParent: null,
      isCookiesEdit: false,
      spinner: false,
      deleteText: 'Löschen',
      totalRows: 1,
      currentPage: 1,
      sideTitle: null,
      iframeSrc: null,
      sideBar: false,
      items: [],
      fields: [
        {key: 'id', label: "Id", sortable: true},
        {key: 'name', label: "Name", sortable: true},
        {key: 'position', label: "Position", sortable: true},
        {key: 'actions', label: 'Actions'}
      ],

    }
  },
  mounted() {
    //make the call to get the records
    this.myProvider();
    window.addEventListener("message", this.eventHandler);
  },
  created() {
  },
  destroyed() {
    window.removeEventListener("message", this.eventHandler);
  },
  computed: {
    emptyTable() {
      if (this.items.length < 1) {
        return "No records!";
      }
      return "No records!";
    },
    getDataParent() {
      return this.dataParent;
    },

    showPaginator() {
      if (this.items && (this.items.length > this.itemsPerPage)) {
        return true;
      }
      return false;
    },
  },
  watch: {

    itemDragged: function (new_value, old_value) {
    }

  },
  methods: {
    showCookie: function (item) {
      this.modal = true;
      this.itemToShow = {item: [item]};
    },
    tableRefreshed: function () {

    },

    hiddenSideBar: function () {

    },
    showSideBar: function () {

    },

    loadCreateTemplate: function () {
      this.sideBar = true;
      this.sideTitle = 'Neues Cookie';
      this.iframeSrc = "/Backend/Cookie/add/" + this.dataParent.id;
    },
    eventHandler: function (event) {

      switch (event.data.op) {
        case 'closeFrame':
          this.sideBar = false;
          this.isCookiesEdit = false;
          this.myProvider();

          if (event.data.newcookie) {
            let msg = 'das Cookie: ' + event.data.newcookie + ' wurde erstellt';
            this.makeToast('success', 'Erfolg!', msg);
          }
          break;
      }
    },
    myProvider() {
      // Here we don't set isBusy prop, so busy state will be
      // handled by table itself
      // this.isBusy = true

      let promise = axios.get(this.appUrl + 'getCookies/' + this.recordId);
      return promise.then((data) => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false.
        //return false if no retrieved data
        if (data.data && (Object.keys(data.data).length > 0 && typeof data.data.parent === 'object')) {
          this.items = data.data.parent.item;
          this.dataParent = data.data.parent;
          //return this.items, this.dataParent;

        } else {
          return false;
        }
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      });
    },
    loadEditTemplateText() {
      this.sideTitle = 'Texten bearbeiten';
      this.iframeSrc = this.appUrl + "edit/" + this.dataParent.id;
      this.sideBar = true;
    },
    loadEditTemplateCookies(item) {

      this.sideTitle = 'Item bearbeiten';
      this.isCookiesEdit = true;
      this.iframeSrc = "/Backend/Cookie/editCookie/" + item.id;
      this.sideBar = true;
    },
    deleteConfirmation(item) {
      this.$bvModal.msgBoxConfirm('Sie sicher sind, dass Sie das Cookie: "' + item.name + '" löschen möchten.', {
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
      }
      let urlDelete = "/Backend/Cookie/deleteCookie";
      axios.get(urlDelete + "/" + params.id).then((data) => {
        if (data.data && (data.data.success)) {
          //check if there are items
          if (this.items.length > 0) {
            this.myProvider();
          }
          this.makeToast('success', 'Erfolg!', data.data.msg);
        }
      });
    },
    makeToast(variant = null, title, msg) {
      this.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      })
    },

    /*DRAG AND DROP EVENTS CALLS*/
    dragStart: function (item) {
      this.itemDragged = item;
    },
    dragLeave: function (item) {
      this.itemClass = 0;
    },

    dragEnter: function (item) {
      //not the same element

      if (this.itemDragged.id !== item.id) {
        this.itemClass = item.id;
      }

    },
    dragDrop: function (item) {
      this.itemClass = 0;
      //return false if the dragged element is the same as the into dropped element
      if (item.id === this.itemDragged.id) {
        return false;
      }

      //swap the rows and save the new order in DB


      //- look the position of the dragged item
      //- look the position of the into dropped item
      let intoDroppedItemPos = item.position;
      let draggedItemPos = this.itemDragged.position;

      //- count the total number of items
      let totalItems = this.items.length;

      //- create an array with the position and the ids of the items
      let itemPositions = [];

      for (let i in this.items) {
        itemPositions[this.items[i].position] =
            {
              id: this.items[i].id,
              position: this.items[i].position,
            };
      }
      //- change the dragged item position


      itemPositions[intoDroppedItemPos] = {
        id: this.itemDragged.id,
        position: intoDroppedItemPos,
      };
      itemPositions[draggedItemPos] = {
        id: item.id,
        position: draggedItemPos,
      };

      //this.items = itemPositions;

      //- save in DB
      this.saveNewPositionOrder(itemPositions);

    },

    saveNewPositionOrder: function (items) {
      let url = this.appUrl + 'changePosition';
      let self = this;
      axios.post(url,
          qs.stringify({data: items}),
          {
            headers: {
              'Content-Type':
                  'application/x-www-form-urlencoded'
            }
          },
      )
          .then(function (response) {
            self.myProvider();
          })
          .catch(function (error) {
            console.log(error);
          });


    },
    iFrameLoaded: function () {
      //only fire if is the case of edition of the cookies
      if (this.isCookiesEdit) {
        //spinner
        this.spinner = true;

        let iFrame = this.$refs.co_iframe;

        let addTagLink = document.createElement('button');
        addTagLink.classList.add('btn-primary');
        addTagLink.classList.add('btn-sm');
        addTagLink.classList.add('w-25');
        addTagLink.classList.add('float-right');
        addTagLink.classList.add('btn');
        addTagLink.innerText = 'Variation einfügen';
        addTagLink.dataset.collectionHolderClass = 'items';

        let divWrapper_ = document.createElement('div');
        divWrapper_.classList.add('w-100');
        //divWrapper_.classList.add('justify-content-end');
        divWrapper_.append(addTagLink);
        // let newLinkLi = document.createElement('li').append(addTagLink);

        let collectionHolder = iFrame.contentWindow.document.querySelector('ul.items');
        collectionHolder.appendChild(divWrapper_);

        let formElements = iFrame.contentWindow.document.querySelectorAll('div#cookie_main_items_form_item > div').forEach((item) => {
          this.addTagFormDeleteLink(item)
        })

        // iFrame.contentWindow.document.querySelector('div#cookie_main_items_form_item').remove();
        addTagLink.addEventListener("click", this.addFormToCollection);

        this.spinner = false;
      }

    },
    addTagFormDeleteLink: function (item) {

      let removeFormButton = document.createElement('button');
      removeFormButton.classList.add('btn');
      removeFormButton.classList.add('btn-sm');
      removeFormButton.classList.add('mr-3');
      removeFormButton.classList.add('btn-danger');
      removeFormButton.innerText = 'Dieses Variation löschen';

      let divWrapper = document.createElement('div');
      divWrapper.classList.add('w-100');
      divWrapper.classList.add('d-flex');
      divWrapper.classList.add('justify-content-end');
      divWrapper.append(removeFormButton);
      item.append(divWrapper);

      removeFormButton.addEventListener('click', (e) => {
        e.preventDefault();
        // remove the li for the tag form
        item.remove();
      });
    },
    addFormToCollection: function (e) {
      let iFrame = this.$refs.co_iframe;
      let collectionHolder = iFrame.contentWindow.document.querySelector('ul.items');
      let item = document.createElement('li');

      item.innerHTML = collectionHolder
          .dataset
          .prototype
          .replace(
              /__name__/g,
              collectionHolder.dataset.index
          );

      collectionHolder.appendChild(item);

      collectionHolder.dataset.index++;
      this.addTagFormDeleteLink(item);
    },

  },


}
</script>

<style lang="scss">
.coo-details-wrapper {
  .coo-cursor-move {
    cursor: move;
  }

  .coo-table-details {
    min-height: 200px !important;
  }

  .coo-overflow-hidden {
    .b-sidebar-body {
      overflow: hidden !important;
    }
  }

  .coo-iframe {
    border: none !important;
  }
}


</style>