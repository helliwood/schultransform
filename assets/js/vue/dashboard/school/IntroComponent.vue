<template>
  <div>
    <div v-if="value<=100">
      <b-progress :value="value" :max="max" show-progress animated></b-progress>

      <!-- show the categories progress   -->
      <div v-for="(item,index) in this.categories">
        <div class="my-2">
          <div v-if="value>=10 && item.id === 1">
            <item-category :category="item"></item-category>
          </div>
          <div v-if="value>=25 && item.id === 2">
            <item-category :category="item"></item-category>
          </div>
          <div v-if="value>=40 && item.id === 3">
            <item-category :category="item"></item-category>
          </div>
          <div v-if="value>=50 && item.id === 4">
            <item-category :category="item"></item-category>
          </div>
          <div v-if="value>=70 && item.id === 5">
            <item-category :category="item"></item-category>
          </div>
          <div v-if="value>=100 && item.id === 6">
            <item-category :category="item"></item-category>
          </div>
        </div>
      </div>
    </div>
    <div v-else class="d-flex justify-content-center my-5 p-3">
      <b-spinner type="grow" label="Loading..." variant="success" class="my-5 mx-auto"></b-spinner>
    </div>

  </div>
</template>

<script>
import ItemCategory from "./ItemCategory";
import axios from "axios";

export default {
  name: "IntroComponent",
  components: {ItemCategory},
  props: {
    categories: null,
    bus: null,
  },
  data: function () {
    return {
      value: 0,
      max: 100,
      interval_: null,
    }
  },
  mounted() {
    this.myInterval();
  },
  destroyed() {
    clearInterval(this.interval_);
  },
  watch: {
    value: function (new_val, old_val) {

      if (new_val >= 110) {
        clearInterval(this.interval_);
        //send the event
        this.bus.$emit('interval-cleared');
      }

    }
  },
  computed: {},
  methods: {
    changeVal: function () {
      this.value = this.value + 5;
      return this.value;
    },
    myInterval: function () {
      let me = this;
      this.interval_ = setInterval(function () {
        this.changeVal();
      }.bind(this), 200);
    },
  },
}
</script>

<style scoped>

</style>