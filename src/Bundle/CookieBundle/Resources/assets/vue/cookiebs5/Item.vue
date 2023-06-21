<template>
  <div>
    <div class="form-check form-switch">
      <input
          :disabled="data.necessary"
          v-model="selection"
          type="checkbox"
          name="check-button"
          :id="'coo-checkbox'+data.id"
          class="coo-checkbox form-check-input"
      >
      <label class="form-check-label" :for="'coo-checkbox'+data.id">Cookies zulassen</label>
    </div>
  </div>
</template>

<script>
export default {
  name: "Item",
  props: {
    data: null,
    index: null,
  },
  data() {
    return {}
  },
  computed: {
    selection: {
      get() {
        let cookies = this.$store.getters["coostore/getTempCookies"];
        //get the value from the store
        if (cookies[this.data.id]) {
          return cookies[this.data.id].checked;
        } else {
          return false;
        }
      },
      //save the value in the store
      set(value) {
        this.$store.dispatch("coostore/dispatchUpdateCookie", {id: this.data.id, checked: value});
      }
    },

  }
}
</script>

<style lang="scss" scoped>

</style>