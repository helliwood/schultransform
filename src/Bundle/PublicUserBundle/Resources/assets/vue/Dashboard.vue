<template>
  <div class="d-flex flex-column mt-0 rounded mb-2">
    <div class="p-2 mb-1 d-flex flex-column flex-lg-row justify-content-between u-dashboard-wrapper">
      <!--Teacher panel-->
      <div class="d-flex flex-column flex-xl-row justify-content-around p-3 rounded d-wrapper-items"
           :class="tableTitle==='teacher'|| tableTitle==='teacherNoSchool' || tableTitle==='teacherWithSchool'?'d-active' : ''">
        <i class="fas fa-chalkboard-teacher d-icon mb-2"></i>
        <div class="ml-3">
          <p class="mb-1">
            <b-link title="Nur die Lehrer in der Tabelle anzeigen" @click="onpressed('teacher')">Lehrer:
              <b>{{ items.teacher }}</b>
              <i class="d-none d-xl-inline fas fa-eye pl-1"></i>
            </b-link>
          </p>
          <p class="mb-1">
            <b-link title="Nur Lehrer mit einer zugewiesenen Schule in der Tabelle anzeigen"
                    @click="onpressed('teacherWithSchool')">Mit Schule: <b>{{
                items.teacherWithSchool
              }}</b>
              <i class="d-none d-xl-inline fas fa-eye pl-1"></i>
            </b-link>
          </p>
          <p class="mb-1">
            <b-link title="Nur Lehrer ohne zugewiesene Schule in der Tabelle anzeigen"
                    @click="onpressed('teacherNoSchool')">Ohne Schule: <b>{{
                items.teacherNoSchool
              }}</b>
              <i class="d-none d-xl-inline fas fa-eye pl-1"></i>
            </b-link>
          </p>

        </div>
      </div>
      <!--School panel-->
      <div class="d-flex flex-column flex-xl-row justify-content-around p-3 rounded d-wrapper-items"
           :class="tableTitle==='school' || tableTitle==='schoolWithTeachers' || tableTitle==='testSchools' ?'d-active' : ''">
        <i class="fas fa-school d-icon mb-2"></i>
        <div class="ml-3">
          <p class="mb-1">
            <b-link title="Nur die Schulen in der Tabelle anzeigen" @click="onpressed('school')">Schulen:
              <b>{{ items.school }}</b>
              <i class="d-none d-xl-inline fas fa-eye pl-1"></i>
            </b-link>
          </p>
          <p class="mb-1">
            <b-link title="Nur die Schulen mit Lehrer in der Tabelle anzeigen" @click="onpressed('schoolWithTeachers')">
              Mit Lehrer:
              <b>{{ items.schoolWithTeachers }}</b>
              <i class="d-none d-xl-inline fas fa-eye pl-1"></i>
            </b-link>
          </p>
          <p class="mb-1">
            <b-link title="Nur die Test Schulen in der Tabelle anzeigen" @click="onpressed('testSchools')">Test
              Schulen:
              <b>{{ items.testSchools }}</b>
              <i class="d-none d-xl-inline fas fa-eye pl-1"></i>
            </b-link>
          </p>
        </div>
      </div>
      <!--School authority panel-->
      <div class="d-flex flex-column flex-xl-row justify-content-around p-3 rounded d-wrapper-items"
           :class="tableTitle==='schoolAuthority' || tableTitle==='testSchoolAuthorities'?'d-active' : ''">
        <i class="fas fa-university d-icon mb-2"></i>
        <div class="ml-3">
          <p class="mb-1">
            <b-link title="Nur die Schulträger in der Tabelle anzeigen" @click="onpressed('schoolAuthority')">
              Schulträger: <b>{{
                items.schoolAuthority
              }}</b>
              <i class="d-none d-xl-inline fas fa-eye pl-1"></i>
            </b-link>
          </p>
          <p class="mb-1">
            <b-link title="Nur die Test Schulträger in der Tabelle anzeigen" @click="onpressed('testSchoolAuthorities')">Test
              Schulträger:
              <b>{{ items.testSchoolAuthorities }}</b>
              <i class="d-none d-xl-inline fas fa-eye pl-1"></i>
            </b-link>
          </p>
        </div>
      </div>
      <!--All users panel-->
      <div class="d-flex flex-column flex-xl-row justify-content-around p-3 rounded d-wrapper-items"
           :class="!tableTitle || tableTitle === 'all'?'d-active' : ''">
        <i class="fas fa-users d-icon mb-2"></i>
        <div class="ml-3">
          <p class="mb-1">
            <b-link title="Alle Benutzer in der Tabelle anzeigen" @click="onpressed('all')">Alle User: <b>{{
                getAllRows
              }}</b>
              <i class="d-none d-xl-inline fas fa-eye pl-1"></i>
            </b-link>
          </p>
        </div>
      </div>
    </div>
    <h4 class="rounded p-1 text-center mt-4 bg-primary text-white d-table-title">{{ getTableTitle }}</h4>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "Dashboard",
  props: {
    onpressed: {type: Function},
    tableTitle: null,
    bus: null,
  },
  data() {
    return {
      items: {
        teacher: null,
        school: null,
        schoolAuthority: null,
        teacherNoSchool: null,
        teacherWithSchool: null,
      },
    }
  },
  created() {
    let this_ = this;
    //reload rows
    this_.bus.$on('sd-reload', function (data) {
      //show msg
      this_.myProvider();
    });
  },
  mounted() {
    this.myProvider();
  },
  computed: {
    getAllRows() {
      return +this.items.teacher + +this.items.schoolAuthority + +this.items.school
    },
    getTableTitle: function () {
      let toReturn = "";
      switch (this.tableTitle) {
        case 'all':
          toReturn = "Alle User in der Datenbank:";
          break;
        case 'teacher':
          toReturn = "Lehrer in der Datenbank:";
          break;
        case 'teacherNoSchool':
          toReturn = "Lehrer ohne zugeordnete Schule:";
          break;
        case 'teacherWithSchool':
          toReturn = "Lehrer mit zugeordneter Schule:";
          break;
        case 'school':
          toReturn = "Schulen in der Datenbank:";
          break;
        case 'schoolWithTeachers':
          toReturn = "Schulen mit verbundenen Lehrern:";
          break;
        case 'schoolAuthority':
          toReturn = "Schulträger in der Datenbank:";
          break;
        case 'testSchools':
          toReturn = "Test Schulen in der Datenbank:";
          break;
        case 'testSchoolAuthorities':
          toReturn = "Test Schulträger in der Datenbank:";
          break;
        default:
          toReturn = this.tableTitle ? this.tableTitle : 'Alle User in der Datenbank:';
      }
      return toReturn;
    }
  },
  methods: {
    myProvider() {
      // Here we don't set isBusy prop, so busy state will be
      // handled by table itself
      // this.isBusy = true

      let promise = axios.get('/Backend/PublicUserBackend/sortedItems');
      return promise.then((data) => {
        /*        this.items.teacher = data.data[0].teacher;
                this.items.school = data.data[0].school;
                this.items.schoolAuthority = data.data[0].schoolAuthority;*/
        this.items = data.data[0];
        return (this.items)
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      });
    }
  },
}
</script>

<style lang="scss" scoped>
.d-table-title {
  -webkit-box-shadow: 0px 9px 4px -7px rgba(0, 0, 0, 0.65);
  -moz-box-shadow: 0px 9px 4px -7px rgba(0, 0, 0, 0.65);
  box-shadow: 0px 9px 4px -7px rgba(0, 0, 0, 0.65);
}

.u-dashboard-wrapper {
  min-height: 10em;

  .d-wrapper-items {
    background: white;
    border: solid 1px gray;
    -webkit-box-shadow: 0 8px 6px -6px black;
    -moz-box-shadow: 0 8px 6px -6px black;
    box-shadow: 0 8px 6px -6px black;
    margin-bottom: 5px;
    margin-right: 5px;
    transition: all 300ms ease-in;
    color: #6b6b6b;

    &.d-active {
      border: solid 1px #979797;
      background: #006292;

      a, b, i {
        color: white !important;
      }
    }

    a {
      color: #313131;

      b {
        color: #313131;
      }

      i {
        color: #939393;
      }
    }

  }

  i.d-icon {
    font-size: 3.3em;
    color: #6b6b6b;
    //box-shadow: rgba(50, 50, 93, 0.25) 0px 13px 27px -5px, rgba(0, 0, 0, 0.2) 0px 8px 16px -8px;
    height: 1em;
    width: 1.4em;
    text-align: center;
    padding: 0 2px;

    &.fa-university {
      font-size: 3.6em;
    }
  }

}


</style>
