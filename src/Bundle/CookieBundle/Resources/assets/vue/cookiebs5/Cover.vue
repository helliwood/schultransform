<template>

  <div class="p-2" v-if="data">
    <!--Content-->
    <p class="lh-base fs-6" v-if="data.content" v-html="data.content"></p>
  </div>

</template>

<script>
export default {
  name: "cover",
  props: {
    data: null,
  },
}
</script>

<style scoped>

</style>