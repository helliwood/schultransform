<template>
  <div class="mb-drop-icons-wrapper">
    <b-button variant="primary" @click.stop="showElement = !showElement">
      <i class="fas fa-sort-down"></i>
    </b-button>
    <!--Edit-->
    <div v-if="showElement" class="mb-drop-div">
      <b-button class="d-flex p-2" @click.stop="onedit(item)" size="sm">
        <i class="fa fa-edit pt-1"></i>
        <span class="ml-1">Edit</span>
      </b-button>

      <!--Go to directory if search active-->
      <b-button v-if="isSearchActive && !folder" class="d-flex p-2" @click.stop="goToDir(item)" size="sm">
        <i class="fa fa-folder-open pt-1"></i>
        <span class="text-nowrap ml-1">Zum Ordner</span>
      </b-button>
      <!--Folder info-->
      <b-button v-if="folder" class="d-flex p-2" @click.stop="oninfo(item)" size="sm">
        <i class="fa fa-info pt-1 text-info"></i>
        <span class="text-nowrap ml-1">Folder Infos</span>
      </b-button>
      <!--Delete-->
      <b-button v-if="!isSearchActive || (isSearchActive && !folder)" class="d-flex p-2" @click.stop="ondelete(item)" size="sm">
        <i class="fa fa-trash text-danger pt-1"></i>
        <span class="ml-1 text-nowrap">Delete</span>
      </b-button>

    </div>
  </div>
</template>

<script>
export default {
  name: "Dropdown",
  props: {
    bus: null,
    folder: null,
    item: null,
    onedit: Function,
    ondelete: Function,
    oninfo: Function,
  },
  data() {
    return {
      showElement: false
    }
  },
  computed: {
    isSearchActive() {
      if(this.$store){
        return this.$store.getters["mediabaseBundleStore/getSearchActive"];
      }
    },
    getRenderMode(){
      if(this.$store){
        return this.$store.getters["mediabaseBundleStore/getRenderMode"];
      }
    },
  },
  methods: {
    /* Go to the directory*/
    goToDir: function (item) {

      if (item &&
          (item.parent && (item.parent.id))
      ) {
        let data = {
          isDirectory: true,
          id: item.parent.id,
        }
        this.bus.$emit('goto-event', {
          data: data,
          op: 'media-details',
          renderMode: this.getRenderMode,
        });
      }
    },

  },
}
</script>

<style lang="scss" scoped>
.mb-drop-icons-wrapper {
  div.mb-drop-div {

    button {
      width: 100%;
    }
  }
}

</style>