<template>
  <div class="coo-details-wrapper">
    <div class="d-flex justify-content-end my-4">
      <b-button variant="primary" v-b-toggle.g-bar @click="loadCreateTemplate">Neues Cookie
      </b-button>
    </div>

    <div>
      <h3 class="mb-4">Gespeicherte Cookie-Banner:</h3>
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
        class="coo-table-main"
        responsive
        fixed
    >
      <template #table-colgroup="scope">
        <col
            v-for="field in scope.fields"
            :key="field.key"
            :style="{ width: field.key === 'id' ? '10%' :''}"
        >
      </template>
      <template v-slot:cell(actions)="row">
        <!--Show cookie action-->
        <a @click="showCookie(row.item)" class="btn btn-sm btn-primary mr-1 d-none d-xl-inline-block"
           size="sm">
          <i class="fas fa-eye"></i> Anzeigen
        </a>
        <!--
        <a size="sm"
           @click="loadEditTemplateText(row.item)"
           class="btn btn-sm btn-primary mr-1">
          <i class="far fa-edit"></i> Texten editieren
        </a>-->
        <!--Edit cookie action-->
        <a size="sm"
           :href="appUrl+'details/'+row.item.id"
           class="btn btn-sm btn-primary mr-1 d-none d-xl-inline-block">
          <i class="far fa-edit"></i> Bearbeiten
        </a>

        <!--Delete action-->
        <a size="sm"
           @click="deleteConfirmation(row.item)"
           class="btn btn-sm btn-danger mr-1 d-none d-xl-inline-block">
          <i class="fas fa-trash-alt"></i>
        </a>

      <!--View port-->
        <b-dropdown id="dropdown-left" text="Bearbeiten" class="d-xl-none" size="sm" variant="primary">
          <!--Show cookie action-->
          <b-dropdown-item variant="primary" @click="showCookie(row.item)">
            <i class="fas fa-eye"></i> Anzeigen
          </b-dropdown-item>

          <!--Edit action-->
          <b-dropdown-item variant="primary" :href="appUrl+'details/'+row.item.id">
              <i class="far fa-edit"></i> Bearbeiten
          </b-dropdown-item>


<!--          <b-dropdown-item variant="primary" @click="loadEditTemplateText(row.item)">
            <i class="far fa-edit"></i> Texten editieren
          </b-dropdown-item>-->

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

      <wrapper :data-cookie="dataCookieShow"></wrapper>


    </b-modal>

  </div>
</template>

<script>
import axios from "axios";
import Wrapper from "./visualization/Wrapper";

export default {
  name: "CookieDashboard",
  components: {
    Wrapper
  },
  props: {
    itemsPerPage: 5,
    appUrl: null,
  },
  data() {
    return {
      modal: false,
      dataCookieShow: null,
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
    showPaginator() {

      if (this.items && (this.items.length > this.itemsPerPage)) {
        return true;
      }
      return false;
    },
  },
  methods: {
    showCookie: function (item) {
      this.modal = true;
      this.dataCookieShow = item;
    },
    hiddenSideBar: function () {

    },
    showSideBar: function () {

    },
    loadCreateTemplate: function () {
      this.sideBar = true;
      this.sideTitle = 'Neues Cookie';
      this.iframeSrc = "/Backend/Cookie/new";
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
    myProvider(l) {
      // Here we don't set isBusy prop, so busy state will be
      // handled by table itself
      // this.isBusy = true

      let promise = axios.get(this.appUrl + 'records');
      return promise.then((data) => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        this.items = data.data;
        return (this.items)
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      });
    },
    loadEditTemplateText(item) {

      this.sideTitle = 'Texten bearbeiten';
      this.iframeSrc = this.appUrl + "edit/" + item.id;
      this.sideBar = true;
    },

    deleteConfirmation(item) {
      this.$bvModal.msgBoxConfirm('Sie sicher sind, dass Sie das Cookie: "' + item.name + '" löschen möchten.', {
        title: 'Bitte bestätigen Sie',
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
      let urlDelete = "/Backend/Cookie/delete";
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
  },
}
</script>


<style lang="scss" scoped>
.coo-details-wrapper {
  .coo-table-main{
    min-height: 200px !important;
  }
}

</style>