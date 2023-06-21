<template>
  <div>
    <b-button-group size="sm" class="ml-1">
      <b-button variant="primary" @click="addSite"><i class="fas fa-plus"></i> Seite hinzufügen</b-button>
    </b-button-group>
    <b-sidebar no-enforce-focus ref="sidebar" id="add-site" title="Seite hinzufügen" width="50%" lazy right shadow
               body-class="border-top" header-class="shadow-sm">
      <iframe v-if="open" width="100%" height="98%"
              :src="'/Backend/ContentTree/Site/add-site' + (this.siteId ? '/'+this.siteId:'')" frameborder="0"></iframe>
    </b-sidebar>
  </div>
</template>

<script>

export default {
  name: "content-tree-actions",
  components: {},
  props: {},
  data() {
    return {
      open: false,
      siteId: null
    }
  },
  mounted() {
    this.$root.$on('tf::content-tree::duplicate-site', (siteId) => {
      this.addSite(siteId);
    });
    window.addEventListener("message", (event) => {
      switch (event.data.op) {
        case 'site-added':
          this.$refs.sidebar.hide();
          // get time to close sidebar
          setTimeout(() => {
            this.$root.$emit('tf::content-tree::refresh-tree');
          }, 500);
          break;
      }
    });
    this.$refs.sidebar.$on("shown", () => {
      this.open = true;
    });
  },
  beforeDestroy() {
    this.$refs.sidebar.hide();
  },
  methods: {
    addSite(siteId = null) {
      this.siteId = siteId;
      this.open = false;
      this.$root.$emit('bv::toggle::collapse', 'add-site');
    }
  }
};
</script>

<style scoped lang="scss">

</style>