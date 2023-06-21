<template>
  <div>
    <div v-if="getIconFromDB">
      <i :class="getIconFromDB"></i>
    </div>
    <div v-else>
      <i :class="iconTypes[getCatId]"></i>
    </div>
  </div>
</template>

<script>
export default {
  name: "CategoryIcon",
  props: {
    categoryId: null,
  },
  data() {
    return {
      //render the icon based on the category number
      iconTypes: {
        1: 'offset-item-icon m-auto  fad fa-bullseye-arrow',
        2: 'offset-item-icon m-auto  fad fa-pennant',
        3: 'offset-item-icon m-auto  fad fa-school',
        4: 'offset-item-icon m-auto  fad fa-users-cog',
        5: 'offset-item-icon m-auto  fad fa-chalkboard-teacher',
        6: 'offset-item-icon m-auto  fad fa-laptop-house',
      },
    }
  },
  computed: {
    getCatId() {
      return this.categoryId;
    },
    getCategoryIcon() {
      return this.$store.getters["qstore/getCategoryIcon"];
    },

    getQuestionnaireIcon() {
      return this.$store.getters["qstore/getQuestionnaireIcon"];
    },

    getIconFromDB() {

      //check if the icon was set
      if (this.getQuestionnaireIcon) {
        return this.getQuestionnaireIcon;
      }
      if (this.getCategoryIcon) {
        return this.getCategoryIcon;
      }
      return false;

    },


  },
}
</script>

<style scoped>

</style>