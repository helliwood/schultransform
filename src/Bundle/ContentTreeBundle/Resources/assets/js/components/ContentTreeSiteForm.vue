<template>
  <div class="content-tree-site-form">
    <slot></slot>
  </div>
</template>

<script>
import * as axios from 'axios';

export default {
  name: "content-tree-site-form",
  components: {},
  props: {
    slugifyPath: String
  },
  data() {
    return {
      timeoutId: null
    }
  },
  mounted() {
    this.$el.querySelector('#site_name').addEventListener("keyup", (event) => {
      clearTimeout(this.timeoutId);
      this.timeoutId = setTimeout(() => {
        this.slugify(event.target.value);
      }, 1000);
    });
    this.$el.querySelector('#site_name').addEventListener("blur", (event) => {
      clearTimeout(this.timeoutId);
      this.slugify(event.target.value);
    });
  },
  methods: {
    slugify(name) {
      axios.get(this.slugifyPath + "?name=" + name).then(result => {
        this.$el.querySelector('#site_slug').value = result.data;
      });
    }
  }
};
</script>

<style scoped lang="scss">

</style>