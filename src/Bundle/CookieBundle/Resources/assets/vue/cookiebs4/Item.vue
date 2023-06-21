<template>
  <div>
    <b-form-checkbox
        :disabled="data.necessary"
        v-model="selection"
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