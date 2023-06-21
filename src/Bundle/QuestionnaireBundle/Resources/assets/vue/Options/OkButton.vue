<template>
  <div class="d-flex align-items-center">
    <button type="button" class="btn text-center text-white q-ok-btn" @click="onclicked" :class="getClass?'q-active-ok-btn':'q-no-active-ok-btn'">OK &#10003;</button>
    <p class="q-color-primary pl-2">drücken Sie Enter &#9166;</p>
  </div>
</template>

<script>
export default {
  name: "OkButton",
  props: {
    onclicked: {type: Function},
    statusClass:null,
  },
  mounted() {
  },
  computed: {
    getClass(){
      return this.statusClass;
    },
  },
  methods: {}
}
</script>

<style scoped>
p{
  font-size: .8em;
}
</style>