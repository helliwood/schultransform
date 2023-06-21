<template>

  <div v-if="data" class="tr-cookie-wrapper-cover">
    <!--Content-->
    <p class="tr-cookie-p-content" v-if="data.content" v-html="data.content"></p>
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

<style lang="scss" scoped>
p {
  font-size: 1em;
}
</style>