<template>
  <div class="content-tree">
    <div>
      <b-button-toolbar class="mb-1" aria-label="Toolbar with button groups and input groups">
        <b-input-group size="sm" prepend="Filter">
          <input class="form-control" type="text" v-model="filter"/>
        </b-input-group>
        <content-tree-actions ref="contentTreeActions"></content-tree-actions>
        <div class="text-center m-1 ml-3 align-content-center" :hidden="loaded">
          <b-spinner label="Spinning" small variant="primary"></b-spinner>
        </div>
      </b-button-toolbar>
      <content-tree-site
          :show="true"
          :index="index"
          :depth="0"
          :site="site"
          :filter="filter"
          v-for="(site, index) in this.tree"
          :key="site.id">
      </content-tree-site>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import ContentTreeSite from "./ContentTree/ContentTreeSite";
import ContentTreeActions from "./ContentTree/ContentTreeActions";

export default {
  name: "content-tree",
  components: {
    ContentTreeSite,
    ContentTreeActions
  },
  props: {
    treePath: String
  },
  data() {
    return {
      loaded: false,
      tree: null,
      filter: null
    }
  },
  mounted() {
    this.load();
    this.$root.$on('tf::content-tree::refresh-tree', () => {
      this.load();
    });
  },
  methods: {
    load() {
      this.loaded = false;
      axios.get(this.treePath).then(result => {
        this.tree = result.data;
        this.loaded = true;
      });
    }
  }
}
</script>

<style scoped lang="scss">
.content-tree {
  width: 100%;
}
</style>