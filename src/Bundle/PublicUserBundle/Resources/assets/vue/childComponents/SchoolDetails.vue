<template>
  <div>
    <b-modal ref="school_modal"
             id="school_modal"
             v-model="modalShow"
             hide-header-close
             hide-footer
             @show="emitEventModalShow"
    >
      <template v-slot:modal-title>
        <div class="d-flex">
          <div>
            <p class="text-dark m-0">Schulname: <b class="text-primary">{{ dataUser.school.name }}</b></p>
            <p v-if="dataUser.school.schoolType && (dataUser.school.schoolType.name)" class="text-dark">Schultyp: <span class="text-primary">{{ dataUser.school.schoolType.name }}</span></p>
            <small class="text-dark">Schule-ID: <b class="text-primary">{{
                dataUser.school.id
              }}</b></small>
            <p class="text-dark m-0 small">Benutzer-ID: <b class="text-primary">{{ dataUser.id }}</b></p>
          </div>
          <!--TEST SCHOOL CHANGE-->
          <div class="ml-5">
            <b-form-checkbox
                id="testSchool"
                v-model="testSchoolCheckbox"
                value="1"
                unchecked-value="0"
                @change="saveValueTestSchool"
            >
              Dies als Testschule festlegen
            </b-form-checkbox>
          </div>
        </div>
        <hr>
        <div>
          <tageditor :school="dataUser.school.id" :tags="dataUser.school.tags"></tageditor>

        </div>

      </template>
      <div class="d-flex justify-content-around p-3">
        <i class="fad fa-school text-primary display-3 m-4"></i>
        <div>
          <div>
            <p class="mb-1" v-if="dataUser.school.confirmed">
              <i class="fad fa-shield-check text-success mr-1 pb-1"></i>
              <span>Schule bestätigt</span>
            </p>
            <p class="mb-1" v-else>
              <i class="fad fa-times-square text-danger"></i>
              <span>Schule nicht bestätigt</span>
            </p>
          </div>
          <div>
            <p class="mb-1">Schuldirektor: <span class="text-primary"
                                                 v-if="dataUser.school.headmaster">{{
                dataUser.school.headmaster
              }}</span><span
                v-else>Nicht verfügbar</span></p>
          </div>
          <div>
            <p class="mb-1">Email: <span class="text-primary"
                                         v-if="dataUser.school.emailAddress">{{
                dataUser.school.emailAddress
              }}</span><span
                v-else>Nicht verfügbar</span></p>
          </div>
          <div>
            <p class="mb-1">Telefonnummer: <span class="text-primary"
                                                 v-if="dataUser.school.phoneNumber">{{
                dataUser.school.phoneNumber
              }}</span><span
                v-else>Nicht verfügbar</span></p>
          </div>
          <div>
            <p class="mb-1">Code: <span class="text-primary" v-if="dataUser.school.code">{{
                dataUser.school.code
              }}</span><span
                v-else>Nicht verfügbar</span></p>
          </div>
          <div>
            <div class="mt-3" v-if="dataUser.school.address">
              <p class="m-0">Anschrift:</p>
              <address class="pl-3">
                <p class="m-0">
                  Stadt: <span class="text-primary">{{ dataUser.school.address.city }}</span>
                </p>
                <p class="m-0">
                  Bundesland: <span class="text-primary">{{ dataUser.school.address.federalState }}</span>
                </p>
                <p class="m-0">
                  Straße: <span class="text-primary">{{ dataUser.school.address.street }}</span>
                </p>
                <p class="m-0">
                  PLZ. <span class="text-primary">{{ dataUser.school.address.postalcode }}</span>
                </p>
              </address>
            </div>
            <div v-else><p>Nicht verfügbar</p></div>
          </div>
        </div>
      </div>
      <p class="p-0 m-0">Link zur Weitergabe an Lehrkräfte:</p>
      <p>
        <b>https://schultransform.de/PublicUser/invite-teacher/{{ dataUser.school.code }}</b>
      </p>
      <div v-if="hasUsersToShow()">
        <b-button class="ml-2" variant="outline-primary" size="sm"
                  @click="loadTeacherUser()">Benutzer(Schule) in der
          Tabelle
          anzeigen(mit oder ohne Lehrer, wenn vorhanden).
        </b-button>
      </div>
      <div v-if="!hasUserThisSchool">
        <p class="ml-2 text-danger">Dieser Schule ist kein Benutzer zugewiesen</p>
      </div>
      <hr>
      <p class="mb-2">Den Lehrern zugewiesene Codes in Bezug auf diese Schule:</p>
      <div v-if="items.length>0" class="d-flex justify-content-end my-3">
        <b-button @click="showTeacherRows()" variant="outline-primary" size="sm">Benutzer (Lehrer) in der Tabelle
          anzeigen
        </b-button>
      </div>
      <b-table
          empty-html="Keine Datensätze gefunden."
          :show-empty="true"
          :items="items"
          :fields="fields"
          sort-icon-left
          :busy="isBusy"
          :per-page="perPage"
          :current-page="currentPage"
          responsive="sm">
      </b-table>
      <div v-if="totalRows > perPage">
        <small>Anzahl der User (Lehrer): <b>{{ totalRows }}</b></small>
        <b-pagination
            v-model="currentPage"
            :total-rows="totalRows"
            :per-page="perPage"
            align="fill"
            size="sm"
            class="my-2"
        ></b-pagination>
      </div>


      <hr>
      <b-button class="mt-3" block @click="modalShow = !modalShow">Schließen</b-button>
    </b-modal>
  </div>
</template>


<script>
import axios from "axios";
import Vue from "vue";
import tageditor from "./tageditor";

export default {
  components: {
    tageditor,
  },
  name: "SchoolDetails",
  props: {
    bus: Vue,
    flag: false,
    dataUser: {
      type: Object
    },
  },
  data() {
    return {
      perPage: 8,
      modalShow: false,
      fields: [
        {key: 'id'},
        {key: 'code'},
      ],
      items: [],
      schoolIdAjax: null,
      schoolUserId: null,
      hasUserThisSchool: false,
      loaded: false,
      currentPage: 1,
      totalRows: 0,
      isBusy: false,
      currentData: null,

    }
  },
  created() {
    console.log('creates  ');
    console.log(this.dataUser);
    console.log(this.dataUser.school);
    this.bus.$on('pu-event', (data) => {
      this.modalShow = true;
    });
  },
  mounted() {
    this.modalShow = true;
    console.log('mount');
    console.log(this.dataUser);
    console.log(this.dataUser.school);

  },
  watch: {
    modalShow: function (n, o) {
      if (n) {
        this.myProvider();
      }
    }
  },
  computed: {
    testSchoolCheckbox: {
      get() {
        if (this.dataUser.school.testSchool === false) {
          return 0;
        }
        if (this.dataUser.school.testSchool === true) {
          return 1
        }
      },
      set(checked) {

      }
    },
  },
  methods: {
    //sent the value of the checkbox to save the testSchool value
    saveValueTestSchool: function (checked) {

      let promise = axios.post('/Backend/PublicUserBackend/setTestSchool',
          "valueSchoolTest=" + checked + '&schoolId=' + this.dataUser.school.id
      );

      return promise.then((data) => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        if (data.data.success) {
          if (data.data.success) {
            //reload the data in the user table
            let dataToSend = {
              name: 'Schule: ' + data.data.schoolName,
              value: checked,
            };
            this.bus.$emit('sd-reload', dataToSend);
          }
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
    hasUsersToShow: function () {
      let toReturn = false;
      //user is the school
      if (this.dataUser.schoolIsTheUser) {
        this.hasUserThisSchool = true;
        this.schoolIdAjax = this.dataUser.school.id;
        this.schoolUserId = this.dataUser.id;

          if (this.dataUser.school && (this.dataUser.school.users)) {
            for (let index in this.dataUser.school.users) {
              if (this.dataUser.school.users[index].code !== '' && this.dataUser.school.users[index].roles.length === 0) {
                toReturn = true;
              }
            }
          }

        }
      return toReturn;
    },
    loadTeacherUser: function () {
      let schoolId = this.schoolIdAjax;
      let schoolUserid = this.schoolUserId;
      let url = '/Backend/PublicUserBackend/sortedBy/users_school_id_' + schoolId + ',' + schoolUserid;
      let dataToSend = {
        'tableTitle': "Schule: " + this.dataUser.school.name + " und verbundene Benutzer (Lehrer): ",
        'url': url,
      }
      this.bus.$emit('load-rows', {arg: dataToSend, op: 'load-rows-teachers'})
      this.modalShow = false;
    },
    showTeacherRows: function () {
      let url = '/Backend/PublicUserBackend/sortedBy/school_id_' + this.dataUser.school.id;
      let dataToSend = {
        'tableTitle': "Schule: " + this.dataUser.school.name + " und verbundene Benutzer (Lehrer): ",
        'url': url,
      }
      this.bus.$emit('load-rows', {arg: dataToSend, op: 'load-rows-teachers'})
      /*      this.$parent.$refs.utable.setApiUrlAndRefresh(url);
            this.$parent.setTableTitle("Schule: " + this.dataUser.school.name + " und verbundene Codes: ");*/
      this.$bvModal.hide('school_modal');

    },
    myProvider: function (cxt) {
      // Here we don't set isBusy prop, so busy state will be
      // handled by table itself
      this.isBusy = true
      if (!this.dataUser.school.id) {
        return false;
      }
      this.items = [];
      let promise = axios.get('/Backend/PublicUserBackend/getTeachersRelated/' + this.dataUser.school.id)

      return promise.then((data) => {
        this.currentPage = 1;
        this.items = data.data
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        this.isBusy = false;
        this.totalRows = data.data.length;
        this.currentData = data.data;
        return this.items;
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
