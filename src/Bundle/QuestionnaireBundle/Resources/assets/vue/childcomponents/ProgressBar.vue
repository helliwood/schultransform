<template>
  <div v-if="!isLoading" class="d-flex flex-row position-absolute q-progress-bar">
    <template v-for="(item,key) in getMatchListForProgressBar">
      <template v-for="elem in item">
        <span v-if="elem[0] == 'group'" class="q-group-span"></span>
      </template>
      <div class="d-flex flex-row q-bar-group">
        <div :class="key == getCatPos - 1 ?'q-bg-primary':'q-group-'+key" class="d-flex flex-row q-group">
          <div v-for="item2 in item" class="d-flex align-items-center">
            <button :class="isSchoolBoard?'q-schoolBoard': ''"></button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
<script>
export default {
  name: "ProgressBar",
  props: {
    catPosition: null,
  },
  data() {
    return {}
  },
  computed: {
    getUserType() {
      return this.$store.getters["qstore/getUserType"];
    },
    isSchoolBoard() {
      return this.getUserType === 'school_board';
    },

    isLoading() {
      return this.$store.getters["qstore/isLoading"];
    },
    getMatchList() {
      //not in use
      return this.matchList;
    },
    getMatchListForProgressBar() {
      return this.$store.getters["qstore/getMatchListForProgressBar"];
    },
    getCatPos() {
      if (this.catPosition) {
        return this.catPosition;
      }
    },
  }
}
</script>

<style lang="scss" scoped>
@import "assets/scss/frontend";

.q-progress-bar {

  top: -8px;
  span.q-group-span:first-child {
    margin: 0 !important;
  }
  span.q-group-span {
    margin-right: 12px;
  }
  .q-group {
    height: 3px;
    overflow: hidden;
    background: gray;

    button {
      border: none;
      width: $q-nav-square-size;
      height: $q-nav-square-size;
      margin: 1px;
      opacity: 0;
    }
    button.q-schoolBoard{
      margin-right: 1.3px !important;
    }
  }
  .q-bar-group {
  }
}
</style>