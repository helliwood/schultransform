<template>
  <div class="schoolTable">
    <SchoolDashboard :bus="bus" :table-title="tableTitle" :onpressed="loadItems" ref="dashboard_users"></SchoolDashboard>
    <div class="filterContainer d-flex align-items-center just">
      <i class="fas fa-search"></i>
      <b-form-input
          v-model="filter"
          type="search"
          id="filterInput"
          @update="inputUpdated"
          placeholder="Filter...">
      </b-form-input>

    </div>
    <data-table
        ref="utable"
        :apiUrl="url"
        :sort-by.sync="sortBy"
        :sort-desc.sync="sortDesc"
        sort-by="order"
        sort-icon-left
        show-empty
        small
        stacked="sm"
        @refreshed="refreshed"
        :filterIncludedFields="filterOn"
        :no-provider-sorting="false"
        :sort-desc="false"
        :per-page="40"
        :fields="fields"

        @filtered="onFiltered"
        :filter="filter"
        :filter-included-fields="filterOn"
        class="table-responsive"
    >

      <!-- A virtual composite column for the name of the institution -->
      <template v-slot:cell(Type)="row">
        <div class="d-flex justify-content-start">
          {{ row.item.schoolType.name }}
        </div>
      </template>
<s></s>
      <!-- Infos-->
      <template v-slot:cell(Infos)="row">
        <div class="d-flex justify-content-start">

          <div v-if="!schoolConfirmed(row.item)">
            <b-button class="d-none d-xl-inline-block" variant="secondary" size="sm"
                      :href="'/PublicUser/create-school-fax/' + row.item.code">
              <i class="fad fa-shield-check text-black-50"></i> <span>Bestätigungsfax</span> <span>erzeugen</span>
            </b-button>
          </div>

          <b-dropdown :title="'Schulinfos'"
                      variant="outline-info" size="sm" class="px-1 d-dropdown">
            <template #button-content>
              <i class="fas fa-info mr-1"></i><span class="d-pu-dnone mr-1">Schule</span>
            </template>
            <b-dropdown-item @click="showSchoolDetailsInModal(row.item)" href="#">
              <!-- Show the school related to the teacher-->
              <b-link class="w-100" :title="'Verbundene Schule '+ row.item.name"
              >
                <i class="fas fa-school text-black-50 mr-1"></i> Schule: {{ row.item.name }}
              </b-link>
            </b-dropdown-item>
            <b-dropdown-item @click="showSchoolAuthorityDetailsInModal(row.item)"
                             href="#">
              <!-- Show the schoolAuthority related to the school-->
              <b-link :title="'Verbundene Schulträger '+ row.item.schoolAuthority.name"
                      variant="secondary"
                      size="sm"
                      class="m-0 w-100"
              >
                <i class="fas fa-university text-black-50"></i>
                Schulträger
              </b-link>
            </b-dropdown-item>
            <b-dropdown-item href="#">
              <!-- Show teachers related to the school-->
              <b-link title="Schule zugewiesene Lehrerinnen und Lehrer" variant="secondary" size="sm"
                      @click="showSchoolDetailsInModal(row.item,true)" :disabled="countTeachers(row.item) < 1">
                <i class="fas fa-chalkboard-teacher mr-1 text-black-50"></i>
                Anzahl von Lehrern: <b
                  :class="countTeachers(row.item) > 0? 'text-primary':'text-danger'">{{
                  countTeachers(row.item)
                }}</b>
              </b-link>
            </b-dropdown-item>

            <b-dropdown-item href="#">
              <!-- Show info for the school-->
              <b-link class="w-100 py-1" title="Informationen über die zugewiesene Schule"
                      @click="showSchoolDetailsInModal(row.item,true)">
                <i class="text-center fas fa-info mr-1 text-black-50"></i>Schulinformationen
              </b-link>
            </b-dropdown-item>
          </b-dropdown>



        </div>
      </template>

      <template v-slot:cell(options)="{ row, callAndRefresh }">
        <div class="d-inline-block d-flex justify-content-end">

          <!-- Confirm school -->
          <div v-if="isUSchool(row.item)" class="mr-2 d-none d-xl-block d-pu-sau-row">
            <div v-if="schoolConfirmed(row.item)"
                 class="rounded border border-success bg-light d-flex flex-row align-items-center h-100">
              <i title="Schule bestätigt" class="fad fa-shield-check text-success mr-1 ut-icon-confirmed ml-1 mb-1"></i>
              <span class="mr-1">Schule</span><span class="mr-1">bestätigt</span><span>|</span>
              <b-link title="Die Bestätigung zu ändern" class="mx-1" @click="confirmSchool(row.item)">Ändern</b-link>
            </div>
            <div v-else title="Schule bestätigen">
              <b-button class="d-none d-xl-inline-block" variant="secondary" size="sm"
                        @click="confirmSchool(row.item)">
                <i class="fad fa-shield-check text-black-50"></i> <span>Schule</span> <span>bestätigen</span>
              </b-button>
            </div>
          </div>

          <b-button class="d-none d-xl-inline-block" variant="primary" size="sm" @click="editItem(row.item)">
            <i class="far fa-edit"></i> Bearbeiten
          </b-button>

          <b-dropdown text="Bearbeiten" class="m-md-2 d-xl-none" size="sm" variant="primary">
            <b-dropdown-item variant="primary" @click.stop="editItem(row.item)"><i class="far fa-edit"></i> Bearbeiten
            </b-dropdown-item>

            <!-- Confirm school-->
            <b-dropdown-item v-if="isUSchool(row.item)" variant="primary">
              <div v-if="schoolConfirmed(row.item)" class="d-flex flex-row align-items-end h-100">
                <i class="fad fa-shield-check text-success mr-1 ut-icon-confirmed pb-1"></i>
                <span>Bestätigt | </span>
                <b-link class="mx-2" @click="confirmSchool(row.item)">Ändern</b-link>
              </div>
              <div v-else>
                <b-link variant="primary" class="text-decoration-none"
                        @click="confirmSchool(row.item)">
                  <i class="fad fa-shield-check text-black-50"></i> Schule bestätigen
                </b-link>
              </div>
            </b-dropdown-item>

            <!-- Confirm school authority-->
            <b-dropdown-item v-if="isUSchoolAuthority(row.item)" variant="primary">
              <div v-if="schoolAuthorityConfirmed(row.item)"
                   class="d-flex flex-row align-items-end h-100">
                <i class="fad fa-shield-check text-success mr-1 ut-icon-confirmed pb-1"></i>
                <span>Bestätigt | </span>
                <b-link class="mx-2" @click="confirmSchoolAuthority(row.item)">Ändern</b-link>
              </div>
              <div v-else>
                <b-link class="text-decoration-none" variant="secondary" size="sm"
                        @click="confirmSchoolAuthority(row.item)">
                  <i class="fad fa-shield-check text-black-50"></i> Schulträger bestätigen
                </b-link>
              </div>
            </b-dropdown-item>

            <b-dropdown-item variant="danger" @click="deleteItem(row.item)"><i class="fas fa-trash-alt"></i> Löschen
            </b-dropdown-item>
          </b-dropdown>
        </div>
      </template>
    </data-table>
    <b-sidebar
        id="user-bar"
        ref="sidebar"
        v-model="showSidebar"
        @shown="barIsShow"
        @hidden="barIsHidden"
        :title="siteBarTitle"
        :width="'50%'"
        lazy right shadow backdrop>
      <iframe :class="frameClass" class="frame" :src="frameUrl" allowfullscreen></iframe>
    </b-sidebar>

    <show-details :bus="this.bus"></show-details>

  </div>
</template>

<script>
import Sortable from "sortablejs";
import axios from 'axios'
import qs from 'qs';
import Vue from "vue";
import ShowDetails from "./ShowDetails";

export default {
  components: {
    ShowDetails,
  },
  name: "school-table",
  props: {
    apiUrl: String
  },
  data() {
    return {
      dashboardTypeUser: 'all',
      rowSelected: '',
      bus: new Vue(),
      tableTitle: null,
      userType: null,
      url: this.apiUrl,
      fields: [
        //{"Typ": {label: '', class: 'd-head-type'}},
        {key: 'id', label: "ID", sortable: true},
        {key: 'name', label: "Schulname", sortable: true},
        {"Type": {label: "Schultyp", sortable: true, class: 'd-head-institution'}},
        {"Infos": {label: 'Infos', class: 'd-head-infos'}},
        //{key: 'options', label: "Aktionen"}
      ],
      sortBy: 'name',
      sortDesc: true,
      sortDirection: 'asc',
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
    let this_ = this;
    this_.bus.$on('load-rows', (data) => {
      this_.$refs.utable.setApiUrlAndRefresh(data.arg.url);
      this_.setTableTitle(data.arg.tableTitle);
      if (this.filter && this.filter !== '') {
        this.filter = null;
      }
    });
    this_.bus.$on('q-side-bar-hidden', function () {
      this_.rowSelected = '';
    });
    this_.bus.$on('d-modal-close', function () {
      this_.rowSelected = '';
    });

    //reload rows
    this_.bus.$on('sd-reload', function (data) {
      //show msg
      this_.showMessage(data)
      this_.refresh();
    });

  },
  mounted() {
    let me = this;
    window.addEventListener("message", (event) => {
      switch (event.data.op) {
        case 'closeFrame':
          this.showSidebar = false;
          this.refresh();
          if (event.data.email) {
            me.makeToast('success', 'E-Mail mit Verifizierungslink wurde gesendet an:', event.data.email);
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
  computed: {},
  watch: {
    filter: function (new_value, old_value) {
      if (new_value && new_value !== '') {
        this.tableTitle = 'Suchfunktion aktiviert: ' + new_value;
      }
    }
  },
  methods: {
    //show a message when an organisation was changed its 'test..' property value
    showMessage: function (data) {
      let textValue = 'wurde geändert';
      let variant = 'success';
      if (data.value == 1) {
        textValue = 'wurde als Test gesetzt';
        variant = 'danger';
      }
      this.makeToast(variant, data.name, textValue);
    },
    //format the date coming from db
    formatDate: function (dateString) {
      let toReturn = dateString;
      let myDate = new Date(dateString);
      if (typeof myDate === 'object') {
        let monthRaw = myDate.getMonth() + 1;
        let month = monthRaw > 9 ? monthRaw : '0' + monthRaw;
        let dayRaw = myDate.getDate();
        let day = dayRaw > 9 ? dayRaw : '0' + dayRaw;
        toReturn = day + '.' + month + '.' + myDate.getFullYear();
      }
      return toReturn;
    },
    //check the registration date and returns a color.
    checkRegistrationDate: function (item) {
      let toReturn = '';
      if (!item.registrationDate) {
        toReturn = 'text-danger';
      } else {
        let dateNow = new Date();
        let dateDb = new Date(item.registrationDate);
        let daysDifference = (dateNow.getTime() - dateDb.getTime()) / (1000 * 60 * 60 * 24);
        //less than 3 days
        if (daysDifference <= 3) {
          toReturn = 'text-black-50';
          //more than 3 but less than 10 days
        } else if (daysDifference > 3 && daysDifference < 10) {
          toReturn = 'tr-text-warning';
          //more than 10 days
        } else if (daysDifference > 10) {
          toReturn = 'text-danger';
        }
      }
      return toReturn;
    },
    makeToast(variant = null, title, msg) {
      this.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true,
        noAutoHide: true,
      })
    },
    //verification link function
    createLinkVerification: function (item) {
      this.showUserDetails(item);
      let emailUser = item.email ? item.email : '';
      let url = '/Backend/PublicUserBackend/create-link/' + item.id;
      this.siteBarTitle = 'E-Mail-Bestätigung erneut senden: ' + "'" + emailUser + "'";
      this.frameUrl = url;
      this.showSidebar = true;

    },
    countTeachers: function (item) {
      let toReturn = 0;
      let currentUserId = item.id;
      let teachersArray = item.usersRelated;
      //verify if the school user is not in the school users array(usersRelated) if not->  do nothing
      if (teachersArray.length) {
        if (!teachersArray.includes(currentUserId)) {
          toReturn = item.userCount;
        } else {
          if (item.userCount > 0) {
            toReturn = item.userCount - 1;
          }
        }
      }
      return toReturn;
    },

    inputUpdated: function (value) {
      //change the api url only if it is differs from the initial
      if (this.$refs.utable) {
        if (this.$refs.utable.url !== '/Backend/School/schooloverview') {
          this.$refs.utable.setApiUrlAndRefresh('/Backend/School/schooloverview');
        }
      }

      if (value === '') {
        this.setTableTitle('all');
        this.loadItems('all')
      }
    },
    setTableTitle: function (title) {
      this.tableTitle = title;
    },
    showUserDetails: function (item, btn) {
      // close the side bar
      if (btn && (btn.btn)) {
        if (this.btnIdClicked === item.id) {
          this.bus.$emit('side-bar-closed');
          this.btnIdClicked = 0;
          return false;
        }
        this.btnIdClicked = item.id;
      }

      this.rowSelected = 'btn_' + item.id; //active icon change color
      item.iconClass = this.getUsertype(item);
      item.userType = this.userType;
      this.bus.$emit('pu-event', {data: item, op: 'user-details'});
    },
    showSchoolDetailsInModal: function (item, isSchool) {
      item.schoolIsTheUser = isSchool ? isSchool : false;
      let foo = {'school':item};


      this.bus.$emit('pu-event', {data: foo, op: 'school-details'});
    },
    showSchoolAuthorityDetailsInModal: function (item, isSchoolAuthority) {
      item.schoolAuthorityIsTheUser = isSchoolAuthority ? isSchoolAuthority : false;
      this.bus.$emit('pu-event', {data: item, op: 'school-authority-details'});
    },
    hasSchoolAuthority: function (item) {
      return !!item.schoolAuthority;
    },
    schoolDetails: function (item) {
      return item.name;
    },

    //is the school already confirmed
    schoolConfirmed: function (item) {
      return item.confirmed;
    },
    //is the school already confirmed
    schoolAuthorityConfirmed: function (item) {
      return !!(item.schoolAuthority && (item.schoolAuthority.confirmed));
    },
    loadItems: function (userType) {
      this.tableTitle = userType;
      this.dashboardTypeUser = userType;
      if (userType === 'all') {
        this.$refs.utable.setApiUrlAndRefresh('/Backend/School/schooloverview');
      } else {
        this.$refs.utable.setApiUrlAndRefresh("/Backend/School/schooloverviewsorted/" + userType);
      }

      if (this.filter && this.filter !== '') {
        this.filter = null;
      }

    },
    refreshed: function () {
    },
    refresh() {
      var self = this;
      self.$refs.utable.$refs.table.isBusy = false;
      self.$refs.utable.$refs.table.$nextTick(function () {
        self.$refs.utable.$refs.table.refresh();
      });
    },
    onFiltered(filteredItems) {
      // Trigger pagination to update the number of buttons/pages due to filtering
      this.$refs.utable.$refs.table.totalRows = filteredItems.length
      this.$refs.utable.$refs.table.currentPage = 1
    },
    deleteItem: function (item) {
    },
    editItem(item) {
      this.showUserDetails(item);
      this.siteBarTitle = 'User bearbeiten';
      // console.log(this.url + '/edit/' + item.id);
      this.frameUrl = this.url + '/edit/' + item.id;
      this.showSidebar = true;
    },
    confirmSchool(item) {
      this.showUserDetails(item);
      this.siteBarTitle = 'Schule bestätigen';
      // console.log(this.url + '/confirmSchool/' + item.id);
      this.frameUrl = this.url + '/confirmSchool/' + item.id;
      this.showSidebar = true;
    },

    createFax(item) {
      this.showUserDetails(item);
      this.siteBarTitle = 'Schulfax';
      // console.log(this.url + '/confirmSchool/' + item.id);
      this.frameUrl = this.url + '/PublicUser/create-school-fax/' + item.code;
      this.showSidebar = true;
    },

    confirmSchoolAuthority(item) {
      this.showUserDetails(item);
      this.siteBarTitle = 'Schulträger bestätigen';
      this.frameUrl = this.url + '/confirmSchoolAuthority/' + item.id;
      this.showSidebar = true;
    },

    barIsShow() {
      this.sidebarIsShown = true;
      if (this.frameLoaded) {
        this.frameClass = "loaded";
      }
    },
    barIsHidden() {
      this.bus.$emit('side-bar-closed');
      this.btnIdClicked = 0; //work together with showUserDetails function
      this.rowSelected = '';
      this.sidebarIsShown = false;
      this.frameClass = '';
      this.showSidebar = false;
    },



  }
}

</script>

<style lang="scss">
@import '@core/scss/backend';

.schoolTable {
  //color tr
  .tr-text-warning {
    color: #cab400;
  }

  .tr-text-sx {
    font-size: .7em;
  }

  .tr-pointer {
    cursor: pointer;
  }

  .ut-icon-confirmed {
    font-size: 1.2em;
  }

  .d-dropdown {
    ul {
      li {

        a {
          padding: 5px 2px;
          text-decoration: none;

          &:active {
            background-color: white;
            color: #006495;

            i {
              color: #006495;
            }
          }
        }
      }
    }

  }

  .d-head-institution {
    span.pu-institution-name {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    div {
      text-align: left;

      p {
        line-height: normal;
        max-width: 90%;
      }
    }
  }

  button.d-no-active-row {
    background: #6b6b6b;
  }

  button.d-active-row {
    //border: solid thin #006292;
    color: white;
    background-color: #006292;

    i {
      color: white !important;
    }

    &:active, &:focus {
      border-color: #006292 !important;
      box-shadow: unset !important;
      background-color: #006292 !important;
    }
  }

  .d-head-email div {
    text-align: left;
  }

  th.d-head-type {
    background: transparent;
  }

  .d-head-type {
    //background: #bad7f3;
    text-align: center;
    width: 2.3em !important;

    .d-p-row-item {
      i {
        font-size: 1.2em;
        color: #FFFFFF;
      }
    }
  }

  .d-pu-sau-row {
    font-size: 0.8em;
  }

  @media (max-width: 577px) {


    .d-table-title {
      font-size: 1em;
    }

    table#my-table {
      thead {
        tr {
          display: none;
        }
      }

      font-size: .9em !important;

      tr {
        display: flex;
        flex-direction: column;
        background: #ffffff;
        border: solid thin #a6a6a6;
        margin-bottom: 5px;
        border-radius: 5px;
        padding: 2px;
      }

      td {

        display: flex;
        flex-direction: column;
        width: 100% !important;
        align-items: flex-start;
        border-top-color: white !important;
        min-width: unset !important;
        max-width: unset !important;

        &:before {
          width: 100% !important;
          text-align: left !important;
        }

        div {
          width: 100% !important;
          padding: 0 !important;

          button {
            width: 100% !important;
          }
        }
      }
    }
  }

  @media (max-width: 678px) {
    td[aria-colindex="4"],
    th[aria-colindex="4"] {
      text-align: right;
      max-width: 150px;
      min-width: 100px;
      white-space: nowrap;
    }
  }

  @media (min-width: 1200px) {
    .table-responsive {
      overflow: visible !important;
    }
  }
  @media (max-width: 1530px) {
    .d-pu-sau-row {
      span {
        &:nth-child(2) {
          display: none;
        }

        &:nth-child(3) {
          display: none;
          text-transform: capitalize;
        }
      }
    }
  }
  @media (max-width: 1320px) {

    span.d-pu-dnone {
      display: none;
    }

  }

  @media (max-width: 1199px) {

    span.d-pu-dnone {
      display: initial;
    }

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

  td[aria-colindex="4"],
  th[aria-colindex="4"] {
    text-align: right;
    max-width: 200px;
    min-width: 180px;
    white-space: nowrap;
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
    max-width: 200px;
    min-width: 180px;
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
}

</style>
