<template>
  <div>
    <component :is="compTypeArr[cType]" :data-user="dataUser" :bus="bus"></component>
  </div>
</template>

<script>
import Vue from "vue";
import SchoolDetails from "./childComponents/SchoolDetails";
import SchoolAuthorityDetails from "./childComponents/SchoolAuthorityDetails";
import UserDetails from "./childComponents/UserDetails";

export default {
  name: "ShowDetails",
  components: {
    SchoolDetails,
    SchoolAuthorityDetails,
    UserDetails
  },

  props: {
    bus: {
      type: Vue,
    },
  },
  created() {
    this.bus.$on('pu-event', (data) => {
      this.cType = data.op;
      this.dataUser = data.data;
      this.temp = this.dataUser.id;

    });
  },
  data() {
    return {
      temp: null,
      dataUser: {},
      compTypeArr: {
        'school-details': SchoolDetails,
        'school-authority-details': SchoolAuthorityDetails,
        'user-details': UserDetails,
      },
      cType: null,
    }
  },
  mounted() {
  },
}
</script>

<style scoped>

</style>