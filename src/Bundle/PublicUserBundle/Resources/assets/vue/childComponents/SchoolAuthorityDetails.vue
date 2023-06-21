<template>
  <div>
    <b-modal ref="school_modal"
             id="school_modal"
             v-model="modalShow"
             @show="emitEventModalShow"
             hide-header-close
             hide-footer
    >
      <template v-slot:modal-title>
        <div class="d-flex">
          <div>
            <p class="text-dark m-0">Schulträgername: <b class="text-primary">{{ dataUser.schoolAuthority.name }}</b>
            </p>
            <p class="text-dark m-0 small">Schulträger-ID: <b class="text-primary">{{ dataUser.schoolAuthority.id }}</b>
            </p>
            <p v-if="dataUser.schoolAuthority.users.length > 0" class="text-dark m-0 small">User-ID: <b
                class="text-primary">{{ dataUser.schoolAuthority.users[0].id }}</b></p>
          </div>
          <!--TEST SCHOOL AUTHORITY CHANGE-->
          <div class="ml-5">
            <b-form-checkbox
                id="testSchoolAuthority"
                v-model="testSchoolAuthorityCheckbox"
                value="1"
                unchecked-value="0"
                @change="saveValueTestSchoolAuthority"
            >
              Dies als Testschulträger festlegen
            </b-form-checkbox>
          </div>
        </div>

      </template>
      <div class="d-flex justify-content-around p-3">
        <i class="fad fa-university text-primary display-3 m-4"></i>
        <div>
          <div>
            <p class="mb-1" v-if="dataUser.schoolAuthority.confirmed">
              <i class="fad fa-shield-check text-success mr-1 pb-1"></i>
              <span>Schulträger bestätigt</span>
            </p>
            <p class="mb-1" v-else>
              <i class="fad fa-times-square text-danger"></i>
              <span>Schulträger nicht bestätigt</span>
            </p>
          </div>
          <div>
            <p class="mb-1">Kontaktperson: <span class="text-primary"
                                                 v-if="dataUser.schoolAuthority.contactPerson">{{
                dataUser.schoolAuthority.contactPerson
              }}</span><span
                v-else>Nicht verfügbar</span></p>
          </div>
          <div>
            <p class="mb-1">Email: <span class="text-primary"
                                         v-if="dataUser.schoolAuthority.emailAddress">{{
                dataUser.schoolAuthority.emailAddress
              }}</span><span
                v-else>Nicht verfügbar</span></p>
          </div>
          <div>
            <p class="mb-1">Telefonnummer: <span class="text-primary"
                                                 v-if="dataUser.schoolAuthority.phoneNumber">{{
                dataUser.schoolAuthority.phoneNumber
              }}</span><span
                v-else>Nicht verfügbar</span></p>
          </div>
          <div>
            <p class="mb-1">Code: <span class="text-primary"
                                        v-if="dataUser.schoolAuthority.code">{{
                dataUser.schoolAuthority.code
              }}</span><span
                v-else>Nicht verfügbar</span></p>
          </div>
          <div>
            <div class="m-1" v-if="dataUser.schoolAuthority.address">
              <p class="m-0">Anschrift:</p>
              <address class="pl-3">
                <p class="m-0">
                  Stadt: <span class="text-primary">{{ dataUser.schoolAuthority.address.city }}</span>
                </p>
                <p class="m-0">
                  Bundesland: <span class="text-primary">{{ dataUser.schoolAuthority.address.federalState }}</span>
                </p>
                <p class="m-0">
                  Straße: <span class="text-primary">{{ dataUser.schoolAuthority.address.street }}</span>
                </p>
                <p class="m-0">
                  PLZ. <span class="text-primary">{{ dataUser.schoolAuthority.address.postalcode }}</span>
                </p>
              </address>
            </div>
          </div>
        </div>
      </div>
      <div v-if="hasSchoolsToShow()">
        <b-button class="ml-2" variant="outline-primary" size="sm"
                  @click="loadSchoolAuthorityUser()">Benutzer(Schulträger) in der
          Tabelle
          anzeigen(mit oder ohne Schulen, wenn vorhanden).
        </b-button>
      </div>
      <div v-if="!hasAUser() && !hasUserThisSchoolAuthority">
        <p class="ml-2 text-danger">Dieser Schulträger ist kein Benutzer zugewiesen</p>
      </div>
      <hr>
      <div v-if="schools.length > 0">
        <h5>Die zugewiesenen Schulen dieser Schulträger:</h5>
        <b-list-group class="mb-1">
          <template v-for="school in schools">
            <b-list-group-item variant="info">Schulname: {{ school.name }}, Code:
              {{ school.code }}, Id: {{ school.id }}
              <small class="p-0 m-0 d-block text-black-50" v-if="!ifValidUser(school)">
                <i class="fas fa-exclamation-triangle text-danger"></i>
                <span>Die Schule hat keinen zugewiesenen Benutzer.</span>
              </small>
            </b-list-group-item>
          </template>
        </b-list-group>

      </div>
      <div v-else>
        <h5 class="text-danger">Der Schulträger hat keine Schule zugewiesen</h5>
      </div>

      <hr>
      <p class="mb-2">Benutzer (Schulen) in Verbindungen mit dieser Schulträger:</p>
      <div v-if="items.length>0" class="d-flex justify-content-end my-3">
        <b-button @click="showSchoolRows()" variant="outline-primary" size="sm">Benutzer (Schulen) in der Tabelle
          anzeigen
        </b-button>
      </div>
      <b-table
          empty-html="Keine Datensätze gefunden."
          :show-empty="true"
          :items="myProvider"
          :fields="fields"
          sort-icon-left
          responsive="sm">
      </b-table>
      <hr>

      <hr>
      <b-button class="mt-3" block @click="modalShow = !modalShow">Schließen</b-button>
    </b-modal>
  </div>
</template>


<script>
import axios from "axios";
import Vue from "vue";

export default {
  name: "SchoolAuthorityDetails",
  props: {
    bus: Vue,
    dataUser: {
      type: Object
    },
  },
  created() {
    this.bus.$on('pu-event', (data) => {
      this.modalShow = true;
    });
  },
  mounted() {
    this.modalShow = true;
  },
  data() {
    return {
      modalShow: false,
      fields: [
        {key: 'id', label: 'User-ID'},
        {key: 'school.name', label: 'Schulname'},
        {key: 'school.code', label: 'Schulcode'},
        /*        {key: 'roles'},*/
      ],
      items: [],
      schools: [],
      hasUserThisSchoolAuthority: false,
      schoolAuthorityIdAjax: null,
      schoolAuthorityUserId: null,
      schoolAuthorityName: null,

    }
  },
  computed: {
    testSchoolAuthorityCheckbox: {
      get() {
        if (this.dataUser.schoolAuthority.testSchoolAuthority === false) {
          return 0;
        }
        if (this.dataUser.schoolAuthority.testSchoolAuthority === true) {
          return 1
        }
      },
      set(checked) {

      }
    },
    countOnlySchools() {
      if (this.items.length < 1) return false;
      let arrayTem = [];
      let arrayToReturn = [];
      for (let index in this.items) {
        if (!arrayTem.includes(this.items[index].school.name)) {
          arrayToReturn.push({
            id: this.items[index].school.id,
            name: this.items[index].school.name,
            code: this.items[index].school.code,
          });
        }
        arrayTem.push(this.items[index].school.name);
      }
      return arrayToReturn;
    },
  },
  methods: {
    //verify if the role of the user is type of school authority
    hasUser: function () {
      if (this.dataUser.roles) {
        if (this.dataUser.roles.includes("ROLE_SCHOOL_AUTHORITY_LITE") || this.dataUser.roles.includes("ROLE_SCHOOL_AUTHORITY")) {
          return true;
        }
      }

    },

    //sent the value of the checkbox to save the testSchoolAuthority value
    saveValueTestSchoolAuthority: function (checked) {

      let promise = axios.post('/Backend/PublicUserBackend/setTestSchoolAuthority',
          "valueSchoolAuthorityTest=" + checked + '&schoolAuthorityId=' + this.dataUser.schoolAuthority.id
      );

      return promise.then((data) => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        if (data.data.success) {
          //reload the data in the user table
          let dataToSend = {
             name: 'Schulträger: '+data.data.schoolAuthorityName,
            value:checked,
          };
          this.bus.$emit('sd-reload',dataToSend);
        }
        this.isBusy = false;
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      });

    },

    emitEventModalShow: function () {
      this.bus.$emit('d-modal-close');
    },
    hasAUser: function () {
      let toReturn = false;
      if (this.dataUser.schoolAuthorityIsTheUser) {
        toReturn = true;
      }
      return toReturn;
    },
    hasSchoolsToShow: function () {
      let toReturn = false;
      //when the user is the school authority
      if (this.dataUser.schoolAuthorityIsTheUser) {
        this.schoolAuthorityUserId = this.dataUser.id;
        this.schoolAuthorityIdAjax = this.dataUser.schoolAuthority.id;
        this.schoolAuthorityName = this.dataUser.schoolAuthority.name;

        if (this.dataUser.schoolAuthority.schools &&
            (this.dataUser.schoolAuthority.schools.length > 0)) {
          toReturn = true;
        }

      } else {
        //when the user is a school
        if (this.dataUser.schoolAuthority
            && (this.dataUser.schoolAuthority.users &&
                this.dataUser.schoolAuthority.users.length > 0)
        ) {
          let users = this.dataUser.schoolAuthority.users;
          for (let index in users) {
            if (users[index].roles.includes('ROLE_SCHOOL_AUTHORITY_LITE')
                || users[index].roles.includes('ROLE_SCHOOL_AUTHORITY')) {

              this.schoolAuthorityUserId = users[index].id;
              this.schoolAuthorityIdAjax = this.dataUser.schoolAuthority.id;
              this.schoolAuthorityName = this.dataUser.schoolAuthority.name;
              this.hasUserThisSchoolAuthority = true;
              toReturn = true;
            }
          }

        }

      }

      return toReturn;
    },
    loadSchoolAuthorityUser: function () {
      let schoolAuthorityId = this.schoolAuthorityIdAjax;
      let schoolAuthorityUsersId = this.schoolAuthorityUserId;
      let schoolAuthorityName = this.schoolAuthorityName;
      let url = '/Backend/PublicUserBackend/sortedBy/users_authority_id_' + schoolAuthorityId + ',' + schoolAuthorityUsersId;
      let dataToSend = {
        'tableTitle': "Schulträger: " + schoolAuthorityName + " und verbundene Benutzer (Schule): ",
        'url': url,
      }
      this.bus.$emit('load-rows', {arg: dataToSend, op: 'load-rows-teachers'})
      this.modalShow = false;
    },
    ifValidUser: function (school) {
      let toReturn = false;
      if (school.users && (school.users.length > 0)) {
        for (let i = 0; i < school.users.length; i++) {
          if (school.users[i].roles.includes('ROLE_SCHOOL') || school.users[i].roles.includes('ROLE_SCHOOL_LITE')) {
            return true;
          }
        }
      }
    },
    showSchoolRows: function () {
      let url = '/Backend/PublicUserBackend/sortedBy/school_authority_id_' + this.dataUser.schoolAuthority.id;
      let dataToSend = {
        'tableTitle': "Schulträger: " + this.dataUser.schoolAuthority.name + " und verbundene Benutzer (Schulen): ",
        'url': url,
      }
      this.bus.$emit('load-rows', {arg: dataToSend, op: 'load-rows-teachers'})
      this.$bvModal.hide('school_modal');

    },
    myProvider: function (cxt) {
      // Here we don't set isBusy prop, so busy state will be
      // handled by table itself
      // this.isBusy = true
      if (!this.dataUser.schoolAuthority.id) {
        return false;
      }
      let promise = axios.get('/Backend/PublicUserBackend/getSchoolsRelated/' + this.dataUser.schoolAuthority.id)

      return promise.then((data) => {
        this.schools = data.data.schools;
        this.items = data.data.users;
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        return (this.items)
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      })
    }


  },
}
</script>

<style scoped>

</style>