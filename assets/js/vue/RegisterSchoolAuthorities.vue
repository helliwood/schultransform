<template>
  <div>
    <div class="d-flex align-items-center mb-2">
      <span class="pr-grid2">Schulträger suchen:</span>
      <div class="flex-fill">
        <div class="input-group">
          <input type="text" class="form-control" placeholder="Geben Sie die PLZ ein!" v-model="search"/>
          <div class="input-group-prepend">
            <span class="input-group-text"><i class="fad fa-search"></i></span>
          </div>
        </div>

      </div>
    </div>
    <div v-if="search && search.length >= 3 && !addSchoolAuthority" style="overflow-x: auto;">
      <table class="table table-striped">
        <thead class="text-muted">
        <tr>
          <td>Name</td>
          <td>Postleitzahl</td>
          <td>Stadt/Gemeinde</td>
          <td></td>
        </tr>
        </thead>
        <tbody>
        <tr v-if="schoolAuthorities.length > 0" v-for="schoolAuthority in schoolAuthorities">
          <td>{{ schoolAuthority.name }}</td>
          <td>{{ schoolAuthority.address.postalcode }}</td>
          <td>{{ schoolAuthority.address.city }}
            {{ schoolAuthority.address.country ? ' (' + schoolAuthority.address.country + ')' : '' }}
          </td>
          <td>
            <button v-if="!schoolAuthority.in_use" class="btn btn-school"
                    @click="setSchoolAuthority(schoolAuthority.id)">Übernehmen
            </button>
            <span v-else>Dieser Träger wird bereits verwaltet!</span>
          </td>
        </tr>
        <tr v-if="schoolAuthorities.length <=0 ">
          <td colspan="4">Es wurden keine Träger im Postleitzahlengebiet '{{ search }}' gefunden!</td>
        </tr>
        <tr>
          <td colspan="3">
            Ist Ihr Träger nicht dabei?
          </td>
          <td>
            <button class="btn btn-primary" @click="setSchoolAuthority(null)">Neu anlegen</button>
          </td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: "register-school-authorities",
  props: {
    url: String,
    setSchoolAuthorityUrl: String,
    nextStep: String
  },
  data() {
    return {
      search: null,
      schoolAuthorities: [],
      awaitingSearch: false
    }
  },
  mounted() {
    this.addSchoolAuthority = this.formSubmitted;
  },
  watch: {
    search: function (val, oldVal) {
      console.log(val);
      clearTimeout(this.awaitingSearch);
      this.awaitingSearch = setTimeout(() => {
        this.searchSchoolAuthorities(val);
        this.awaitingSearch = false;
      }, 500); // 500 ms delay
    }
  },
  methods: {
    searchSchoolAuthorities: function (search) {
      axios.get(this.url + '?search=' + search).then(response => {
        console.log(response.data);
        this.schoolAuthorities = response.data;
      });
    },
    setSchoolAuthority: function (id) {
      axios.get(this.setSchoolAuthorityUrl + '?school-authority-id=' + id).then(response => {
        console.log(response.data);
        location.href = this.nextStep;
      });
    }
  }
}
</script>

<style lang="scss" scoped>
td {
  vertical-align: middle;
}
</style>