<template>
<div>
    <div class="legend my-2">
    <span class="unread">ungelesen</span> |  <span class="forwarded">weitergeleitet</span>
    </div>

  <data-table
      sort-by="creationDate"
      id="SpamMailListing"
      :sort-desc="true"
      :tbody-tr-class="rowClass"
      @row-clicked="item=>$set(item, '_showDetails', !item._showDetails)"
      :apiUrl="url"
      :fields="[
                    {key: 'subject', label: 'Betreff', sortable: true},
                    {key: 'emailAddress', label: 'Absender', sortable: true},
                    {key: 'creationDate', label: 'Datum', sortable: true,
                        formatter: (value, key, item) => {
                        return moment(value.date).format('DD.MM.YYYY HH:mm')
                      }
                    },
                    {key: 'actions', label: 'Optionen', sortable: false, class:'options'}

                    ]"
  >
    <template v-slot:cell(subject)="{row, callAndRefresh}">
      {{ row.item.subject }}
    </template>
    <template #row-details="row">
      <b-card class="py-2 px-4" >
        <div v-html="row.item.body" class="mb-5"></div>
        <div class="d-flex mt-5">
          <b-button variant="primary" size="xl"  class="col"  v-b-modal="'modal-resend-spam-'+row.item.id">
            Neu versenden
          </b-button>
          <b-button variant="danger"
                    size="xl" v-b-modal="'modal-delete-spam-'+row.item.id"
                    class="col"><i class="fas fa-trash"></i></b-button>
        </div>
      </b-card>
    </template>
    <template v-slot:cell(actions)="{row, callAndRefresh}">
      <b-button
                size="sm" v-b-modal="'modal-resend-spam-'+row.item.id"
                class="mr-2">neu versenden</b-button>
      <b-modal :id="'modal-resend-spam-'+row.item.id"
               title="Spammail erneut versenden"
               @ok="callAndRefresh({action:'resend_spammail', spammail_id:row.item.id, send_to:themail})"
               ok-variant="success"
               ok-title="Spammail erneut versenden"
               cancel-title="Abbrechen" cancel-variant="primary-light">

        <p class="">Wohin soll die E-Mail versendet werden?</p>
        <input  v-model="themail" type="email" class="w-100"/>
      </b-modal>

      <b-button size="sm" @click="row.toggleDetails" class="mr-2">
        {{row.detailsShowing ? "Verbergen" : 'Anzeigen'}}
      </b-button>
      <b-button variant="danger"
                size="sm" v-b-modal="'modal-delete-spam-'+row.item.id"
                class="mr-2"><i class="fas fa-trash"></i></b-button>
      <b-modal :id="'modal-delete-spam-'+row.item.id"
               title="Spammail löschen"
               @ok="callAndRefresh({action:'delete_spammail', spammail_id:row.item.id})"
               ok-variant="danger"
               ok-title="Spammail löschen"
               cancel-title="Abbrechen" cancel-variant="primary-light">
        <p class="">Möchten Sie die Spammail wirklich löschen?</p>
      </b-modal>
    </template>

  </data-table>
</div>
</template>

<script>
import axios from 'axios'
import qs from 'qs';
import Vue from "vue";

export default {
  name: "spam-table",
  props: {
    apiUrl: String,
    userMail: String
  },
  data() {
    return {
      dashboardTypeUser: 'all',
      themail:this.userMail,
      rowSelected: '',
      bus: new Vue(),
      tableTitle: null,
      userType: null,
      url: this.apiUrl,
      fields: [
        {key: 'subject', label: 'Betreff', sortable: true},
        {key: 'emailAddress', label: 'Absender', sortable: true},
        {key: 'creationDate', label: 'Datum', sortable: true,
          formatter: (value, key, item) => {
            return moment(value.date).format('DD.MM.YYYY HH:mm')
          }
        },
        {key: 'actions', label: 'Optionen', sortable: false, class:'options'}

      ],
      sortBy:"creationDate",
      rowClicked:"item=>$set(item, '_showDetails', !item._showDetails);",
      showSelect:true,
      sortDesc: true,
      sortDirection: 'desc',
      totalRows: 1,
      currentPage: 1,
      filter: null,
      filterOn: [],
      items: [],
      siteBarTitle: '',
      showSidebar: false,
      sidebarIsShown: false,
      frameUrl: '',
      frameLoaded: false,
      frameClass: '',
      btnIdClicked: 0,
    }
  },
  created() {

  },
  mounted() {

  },
  computed: {},

  methods: {
    rowClass(item, type) {
      if (item && type === 'row') {
        if (item.status == 1) {
          return 'read';
        }
        else if(item.status == 2){
          return 'forwarded';
        }
        else {
          return 'unread';
        }
      } else {
        return null
      }
    }
  }
}

</script>

<style lang="scss">

.unread{
  font-weight: bold;
}
.forwarded{
  opacity: 0.4;
  font-style: italic;
}
</style>
