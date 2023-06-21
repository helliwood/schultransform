<template>
  <div>
    <b-sidebar
        v-model="showSideBar"
        id="sidebar-right"
        title="Benutzer Infos"
        left
        shadow
        @hidden="isHidden">
      <div class="px-3 py-2">
        <p v-html="dataUser.iconClass" class="border rounded p-3 display-3 text-center text-white bg-primary">
        </p>
        <h4><b>{{ getUserType(dataUser.userType) }}:</b></h4>
        <!-- school -->
        <div v-if="dataUser.userType === 'school' && dataUser.school">
          <div class="d-flex">
            <!--If it is a test school-->
            <div>
              <small v-if="dataUser.school.testSchool"
                     class="d-flex text-center text-white flex-column bg-info border p-1 mx-1"
                     title="Dies ist eine Test Schule">
                <i class="fas fa-vial"></i>
                Test
              </small>
            </div>
            <h4 class="text-primary mb-0">{{ dataUser.school.name }}</h4>
          </div>
          <small>Schule-ID: <b class="text-primary">{{
              dataUser.school.id
            }}</b></small>
          <p v-if="dataUser.school.code">Schulcode: {{ dataUser.school.code }}</p>
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
          <hr>
          <div v-if="dataUser.school.schoolAuthority">
            <p class="mb-1">Schulträger in Bezug auf die Schule:
            </p>
            <div class="p-2 m-1 bg-secondary rounded">
              <!-- If test school authority-->
              <div class="d-flex">
                <small v-if="dataUser.school.schoolAuthority.testSchoolAuthority"
                       class="d-flex text-center text-white flex-column bg-info border p-1 mx-1"
                       title="Dies ist eine Test Schulträger">
                  <i class="fas fa-vial"></i>
                  Test
                </small>
                <p class="mb-0"><b class="text-primary">{{ dataUser.school.schoolAuthority.name }}</b></p>
              </div>
            </div>
            <div>

              <small class="ml-2">Schulträger-ID: <b class="text-primary">{{
                  dataUser.school.schoolAuthority.id
                }}</b></small>
              <p class="m-2" v-if="dataUser.school.schoolAuthority.confirmed">
                <i class="fad fa-shield-check text-success mr-1 pb-1"></i>
                <span>Schulträger bestätigt</span>
              </p>
              <p class="m-2" v-else>
                <i class="fad fa-times-square text-danger"></i>
                <span class="text-">Schulträger nicht bestätigt</span>
              </p>
              <div v-if="dataUser.school.schoolAuthority.users && (dataUser.school.schoolAuthority.users.length > 0)">
                <b-button class="ml-2" variant="outline-primary" size="sm"
                          @click="loadSchoolUser(dataUser.school.schoolAuthority.id)">Benutzer(Schulträger) in der
                  Tabelle
                  anzeigen
                </b-button>
              </div>
              <div v-else>
                <p class="ml-2 text-danger">Dieser Schulträger ist kein Benutzer zugewiesen</p>
              </div>

            </div>
          </div>
          <hr>
          <p class="text-black-50 m-0" v-if="dataUser.firstName || dataUser.lastName">Benutzerdaten:</p>
          <p class="text-primary m-0">
            <span v-if="dataUser.salutation">{{ dataUser.salutation }}:</span>
            <span v-if="dataUser.firstName">{{ dataUser.firstName }}</span>
            <span v-if="dataUser.lastName">{{ dataUser.lastName }}</span>
          </p>
          <p class="text-black-50 m-0" v-if="dataUser.email">E-mail: <span
              class="text-primary">{{ dataUser.email }}</span></p>
          <small class="text-black-50">Benutzer Id:
            <b class="text-primary">{{ dataUser.id }}</b>
          </small>
        </div>
        <!-- schoolAuthority -->
        <div v-if="dataUser.userType === 'schoolAuthority' && dataUser.schoolAuthority">
          <div class="d-flex">
            <!-- If test school authority-->
            <div>
              <small v-if="dataUser.schoolAuthority.testSchoolAuthority"
                     class="d-flex text-center text-white flex-column bg-info border p-1 mx-1"
                     title="Dies ist eine Test Schulträger">
                <i class="fas fa-vial"></i>
                Test
              </small>
            </div>
            <h4 class="text-primary mb-1">{{ dataUser.schoolAuthority.name }}</h4>
          </div>

          <small>Schulträger-ID: <b class="text-primary">{{
              dataUser.schoolAuthority.id
            }}</b></small>
          <p v-if="dataUser.schoolAuthority.code">Schulträgercode: {{ dataUser.schoolAuthority.code }}</p>
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
          <hr>
          <p class="text-black-50 m-0" v-if="dataUser.firstName || dataUser.lastName">Benutzerdaten:</p>
          <p class="text-primary m-0">
            <span v-if="dataUser.salutation">{{ dataUser.salutation }}:</span>
            <span v-if="dataUser.firstName">{{ dataUser.firstName }}</span>
            <span v-if="dataUser.lastName">{{ dataUser.lastName }}</span>
          </p>
          <p class="text-black-50 m-0" v-if="dataUser.email">E-mail: <span
              class="text-primary">{{ dataUser.email }}</span></p>
        </div>
        <!-- teacher -->
        <div v-if="dataUser.userType === 'teacher' && dataUser.code !== ''">
          Code: <b class="text-primary">{{ dataUser.code }}</b>
          <hr>
          <div v-if="dataUser.school">
            <p class="mb-1">Schule in Bezug auf den Lehrer:
            </p>
            <div class="p-2 m-1 bg-secondary rounded">
              <div class="d-flex">
                <!--If it is a test school-->
                <div>
                  <small v-if="dataUser.school.testSchool"
                         class="d-flex text-center text-white flex-column bg-info border p-1 mx-1"
                         title="Dies ist eine Test Schule">
                    <i class="fas fa-vial"></i>
                    Test
                  </small>
                </div>
                <p class="mb-0"><b class="text-primary">{{ dataUser.school.name }}</b></p>
              </div>
              <small class="ml-2">Schul-ID: <b class="text-primary">{{
                  dataUser.school.id
                }}</b></small>
              <p class="m-2" v-if="dataUser.school.confirmed">
                <i class="fad fa-shield-check text-success mr-1 pb-1"></i>
                <span>Schule bestätigt</span>
              </p>
              <p class="m-2" v-else>
                <i class="fad fa-times-square text-danger"></i>
                <span>Schule nicht bestätigt</span>
              </p>
              <div class="m-2" v-if="schoolHasUser(dataUser)">
                <b-button variant="outline-primary" size="sm" @click="loadTeacherUser(dataUser.school.id)">
                  Benutzer(Schule) in der Tabelle anzeigen
                </b-button>
              </div>
              <div v-else>
                <p class="m-2 text-danger">Dieser Schul ist kein Benutzer zugewiesen</p>
              </div>

            </div>
            <hr>
          </div>
          <div v-if="dataUser.code">
            <p class="text-black-50 m-0">Benutzerdaten:</p>
            <small class="text-black-50">Benutzer Id:
              <b class="text-primary">{{ dataUser.id }}</b>
            </small>
          </div>
          <!--Questionnaires filled out-->
          <div class="my-1" v-if="questionnairesFilledOut && (this.questionnairesFilledOut.length >0)">
            <hr>
            <p class="p-0 m-0 mb-1">
              Anzahl der ausgefüllten Fragebögen:
              <b class="text-primary">{{ questionnairesFilledOut.length }}</b>
            </p>
            <ul>
              <li v-for="questionnaire in questionnairesFilledOut">
                <small>{{ questionnaire.name }}</small>
              </li>
            </ul>
          </div>
        </div>
        <!-- User unknown -->
        <div class="text-primary" v-if="dataUser.userType === 'unknown'">

          <p v-html="getUnknownUser"></p>
          <div>
            <hr>
            <p class="text-black-50 m-0">Benutzerdaten:</p>
            <p class="text-primary m-0">
              <span v-if="dataUser.salutation">{{ dataUser.salutation }}:</span>
              <span v-if="dataUser.firstName">{{ dataUser.firstName }}</span>
              <span v-if="dataUser.lastName">{{ dataUser.lastName }}</span>
            </p>
            <p class="text-black-50 m-0" v-if="dataUser.email">E-mail: <span
                class="text-primary">{{ dataUser.email }}</span></p>
            <small class="text-black-50">Benutzer Id:
              <b class="text-primary">{{ dataUser.id }}</b>
            </small>
          </div>
        </div>


      </div>
    </b-sidebar>
  </div>
</template>


<script>

import Vue from "vue";
import axios from "axios";

export default {
  name: "UserDetails",
  props: {
    bus: Vue,
    dataUser: {
      type: Object
    },
  },
  data() {
    return {
      showSideBar: false,
      userInfos: {},
      questionnairesFilledOut: null,
    }
  },
  created() {
    this.bus.$on('pu-event', (data) => {
      this.showSideBar = true;
    });

    this.bus.$on('side-bar-closed', (data) => {
      this.showSideBar = false;
    });

  },
  mounted() {
    if (this.dataUser && (this.dataUser.userType === 'teacher')) {
      this.getQuestionnairesFilledOut(this.dataUser.id);
    }
    this.showSideBar = true;
  },
  beforeUpdate() {
    if (this.dataUser.userType === 'school' || this.dataUser.userType === 'schoolAuthority') {
      this.userInfos = this.myProvider();
    }
  },
  watch: {
    dataUser: function (n, o) {
      if (n) {
        if (this.dataUser && (this.dataUser.userType === 'teacher')) {
          this.questionnairesFilledOut = null;
          this.getQuestionnairesFilledOut(this.dataUser.id);
        }
      }
    }
  },
  computed: {

    getUnknownUser() {
      if (
          this.dataUser.roles.includes('ROLE_SCHOOL') ||
          this.dataUser.roles.includes('ROLE_SCHOOL_LITE')
      ) {
        return "<b class='text-danger'>Benutzer ist als Schule gekennzeichnet, hat aber keine Schule Id zugewiesen bekommen.</b>";
      } else if (
          this.dataUser.roles.includes('ROLE_SCHOOL_AUTHORITY')
          || this.dataUser.roles.includes('ROLE_SCHOOL_AUTHORITY_LITE')
      ) {
        return "<b class='text-danger'>Benutzer ist als Schulträger gekennzeichnet, hat aber keine Schulträger Id zugewiesen bekommen.</b>";
      }
    }
  },
  methods: {
    getQuestionnairesFilledOut: function (userId) {
      if (!this.dataUser.id) {
        return false;
      }
      let promise = axios.get('/Backend/PublicUserBackend/getQuestionnairesFilledOut/' + userId)

      return promise.then((data) => {
        this.questionnairesFilledOut = data.data
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        return (this.userInfos)
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      })
    },
    schoolHasUser: function (user) {
      let toReturn = false;
      if (user.school && (user.school.users)) {
        for (let index in user.school.users) {
          if (user.school.users[index].roles.includes('ROLE_SCHOOL') || user.school.users[index].roles.includes('ROLE_SCHOOL_LITE')) {
            toReturn = true;
          }
        }
      }
      return toReturn;
    },
    myProvider: function (cxt) {
      // Here we don't set isBusy prop, so busy state will be
      // handled by table itself
      // this.isBusy = true
      if (!this.dataUser.id) {
        return false;
      }
      let promise = axios.get('/Backend/PublicUserBackend/getUserInfos/' + this.dataUser.id)

      return promise.then((data) => {
        this.userInfos = data.data

        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        return (this.userInfos)
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      })
    },
    loadSchoolUser: function (schoolAuthorityId) {
      let url = '/Backend/PublicUserBackend/sortedBy/user_with_authority_id_' + this.dataUser.id + ',' + schoolAuthorityId;
      let dataToSend = {
        'tableTitle': "Schulträger: " + this.dataUser.school.schoolAuthority.name + " und verbundene Benutzer (Schule): " + this.dataUser.school.name,
        'url': url,
      }
      this.bus.$emit('load-rows', {arg: dataToSend, op: 'load-rows-teachers'})
    },
    loadTeacherUser: function (schoolId) {
      let url = '/Backend/PublicUserBackend/sortedBy/user_with_school_id_' + this.dataUser.id + ',' + schoolId;
      let dataToSend = {
        'tableTitle': "Schule: " + this.dataUser.school.name + " und verbundene Benutzer (Lehrerin/er): " + this.dataUser.code,
        'url': url,
      }
      this.bus.$emit('load-rows', {arg: dataToSend, op: 'load-rows-teachers'})
    },
    isHidden: function () {
      this.bus.$emit('q-side-bar-hidden');
    },
    getUserType: function (usertype) {
      let toReturn = '';
      switch (usertype) {
        case 'teacher':
          toReturn = 'Lehrer';
          break;

        case 'school':
          toReturn = 'Schule';
          break;

        case 'schoolAuthority':
          toReturn = 'Schulträger';
          break;

        case 'unknown':
          toReturn = 'Unbekannt ';
          break;

      }
      return toReturn;
    },
  },
}
</script>

<style scoped>

</style>