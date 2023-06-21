<template>
  <div>
    <b-form-checkbox
        :disabled="data.necessary"
        :checked="data.necessary"
        name="check-button"
        class="coo-checkbox"
        switch>
      Cookies zulassen
    </b-form-checkbox>
  </div>
</template>

<script>
export default {
  name: "Item",
  props: {
    data: null,
    index:null,
  },
  data() {
    return {}
  },
  computed: {


  }
}
</script>

<style lang="scss" scoped>

</style>