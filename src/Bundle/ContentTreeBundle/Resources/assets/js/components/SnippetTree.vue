<template>
  <div class="d-inline-block">
    <b-button variant="primary" @click="showTree"><i class="fas fa-stream"></i> Snippet-Tree anzeigen</b-button>
    <b-sidebar no-enforce-focus ref="sidebar" id="show-tree" title="Snippet-Tree" width="35%" lazy right shadow
               body-class="border-top" header-class="shadow-sm">
      <div class="m-3">
        <snippet-tree-item :item="item" v-for="(item, i) in tree" :key="item.id"></snippet-tree-item>
      </div>
    </b-sidebar>
  </div>
</template>

<script>

import SnippetTreeItem from "./SnippetTreeItem";

export default {
  name: "snippet-tree",
  components: {SnippetTreeItem},
  props: {
    tree: Array
  },
  data() {
    return {
      open: false
    }
  },
  mounted() {
    this.$refs.sidebar.$on("shown", () => {
      this.open = true;
    });
  },
  beforeDestroy() {
    this.$refs.sidebar.hide();
  },
  methods: {
    showTree() {
      this.open = false;
      this.$root.$emit('bv::toggle::collapse', 'show-tree');
    }
  }
};
</script>

<style scoped lang="scss">

</style>