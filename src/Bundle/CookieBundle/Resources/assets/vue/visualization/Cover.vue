<template>

  <div v-if="data">
    <!--Title-->
    <h5 v-if="data.title">{{data.title}}</h5>
    <!--Content-->
    <p v-if="data.content">{{data.content}}</p>
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