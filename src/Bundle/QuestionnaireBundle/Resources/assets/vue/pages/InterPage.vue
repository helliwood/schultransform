<template>
  <div><p>Inter page</p></div>
</template>

<script>
export default {
  name: "InterPage",
  mounted() {

    //retrieving a flag in order to know where that comes from: nav or btn s(arrows)
    if (this.$store.getters["qstore/getSwitchFlagNav"]) {
      let flag_ = {flag: 'next'};
      this.$store.dispatch("qstore/updatePointer", flag_);
      //set the switchFlagNav to false
      this.$store.dispatch("qstore/changeSwitchFlagNav", false);
    } else {
      //this is working for the navigation with the arrows: up & down
      if (this.$store.getters["qstore/getClassAnimate"] === 'animate-questionnaire-next') {
        let flag_ = {flag: 'next'};
        this.$store.dispatch("qstore/updatePointer", flag_);
      } else if (this.$store.getters["qstore/getClassAnimate"] === 'animate-questionnaire-back') {
        let flag_ = {flag: 'back'};
        this.$store.dispatch("qstore/updatePointer", flag_);
      }
    }



  },

}
</script>

<style scoped>

</style>