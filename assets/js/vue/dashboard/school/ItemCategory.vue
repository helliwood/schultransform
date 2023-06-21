<template>
  <div v-if="category" class="d-flex" :class="'c-'+category.id">
    <div class='tr-name-bg tr-sm-w-15 tr-md-w-10 tr-w-5 tr-display-4 mr-1 mr-sm-1 text-center'>
      <i :class="'text-white fad '+ category.icon"></i>
    </div>

    <div class="pr-0 tr-border-2 overflow-hidden flex-grow-1 d-flex flex-column justify-content-center">
      <div class="pl-1">
        <p class="tr-ellipsis">{{ category.name }}</p>
      </div>
    </div>
    <b-button class="p-1">OK</b-button>
  </div>
</template>

<script>
export default {
  name: "ItemCategory",
  props:{
    category:null,
  },
}
</script>

<style scoped>

</style>