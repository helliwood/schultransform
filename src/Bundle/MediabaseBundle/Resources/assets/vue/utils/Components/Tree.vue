<template>
  <div v-if="data">
    <div>
      <h5>Ordner:
        <span v-if="getFolderName">{{ getFolderName }}</span>
      </h5>
      <p class="m-0 pl-2" v-if="getFolderSize">Größe: {{ getFolderSize }}</p>
      <p class="m-0 pl-2" v-if="getNumberOfFiles">Dateien: {{ getNumberOfFiles }}</p>

    </div>
    <hr>
    <h5>Ordner Navigation</h5>
    <ul>
      <!-- Component to create the folder structure: first use is only to show the first level of data -->
      <tree-item
          class="item"
          :item="data.tree"
          :root="data.folderName"
          :rootFolderId="data.id"
          :onshow="onShow"
          :loadFolder="onLoadFolder"
          :current-show-item="currentShowItem"
          :bus="bus"
          :openclose="onShow"
      ></tree-item>
    </ul>
    <hr>
    <!-- it shows the details of the clicked file-->
    <div v-if="htmlContent !==''">
      <h5>Details</h5>
      <div class="mt-3 p-2 border" v-html="htmlContent">
      </div>
    </div>

  </div>

</template>

<script>
import axios from "axios";
import TreeItem from "./TreeItem";

export default {
  name: "Tree",
  components: {
    TreeItem,
  },
  props: {
    currentFolderId: null,
    bus: null,
  },
  data: function () {
    return {
      data: null,
      htmlContent: '',
      currentShowItem: null,
    };
  },

  mounted() {
    this.myProvider();
  },
  computed: {
    getData() {
      if (this.data) {
        return this.data;
      }
    },
    getFolderName() {
      if (this.getData && (this.getData.folderName)) {
        return this.getData.folderName;
      }
    },
    getNumberOfFiles() {
      if (this.getData && (this.getData.numberOfFiles)) {
        return this.getData.numberOfFiles;
      }
    },

    getFolderSize() {
      if (this.getData && (this.getData.size)) {
        return this.getData.size;
      }
    },
  },
  methods: {

    onLoadFolder: function (item) {
      //clear the search
      this.$store.dispatch("mediabaseBundleStore/dispatchSearchActive", false);

      //clear the item selected
      this.currentShowItem = 0;
      this.htmlContent = '';
      //if not 0: bool. Base directory is flagged with the id = 0
      if (item.id) {
        this.bus.$emit('goto-event', {
          data: {id: item.id, isDirectory: true, name: item.name},
          op: 'media-details',
          renderMode: this.$store.getters["mediabaseBundleStore/getRenderMode"]
        });
      } else {
        //not root directory. This runs when the base directory was clicked.
        if (this.data.id && this.data.id !== 0) {
          this.bus.$emit('goto-event', {
            data: {mimeType: "toParent", parent: {id: this.data.id, name: this.data.folderName}},
            op: 'media-details',
            renderMode: this.$store.getters["mediabaseBundleStore/getRenderMode"],
          });
        } else {
          //root(parent af all directories) directory
          this.bus.$emit('goto-event', {
            data: {mimeType: "toParent"},
            op: 'media-details',
            renderMode: this.$store.getters["mediabaseBundleStore/getRenderMode"],
          });
        }
      }

    },
    onShow: function (item, data) {
      if (data.type && (data.type === 'folder')) {
        this.htmlContent = '';
        this.currentShowItem = 0;
      } else if (data.type && (data.type === 'file')) {
        this.currentShowItem = item.id;
        axios.get('/Backend/MediaBase/DetailsIconView/' + item.id).then(resp => {
          this.htmlContent = resp.data;
        });
      }

    },
    myProvider() {
      // Here we don't set isBusy prop, so busy state will be
      // handled by table itself
      // this.isBusy = true
      let url = null;
      if (this.currentFolderId) {
        url = '/Backend/MediaBase/InfoFolder/' + this.currentFolderId;
      } else {
        url = '/Backend/MediaBase/InfoRootFolder';
      }
      if (!url) {
        return false;
      }
      let promise = axios.get(url);
      return promise.then((data) => {

        this.data = data.data;
        // this.items = data.data[0];
        return (this.data)
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      });

    },
  },

}
</script>

<style lang="scss">

span.mb-tree-icon-active {
  color: #006495;

  i {
    color: #006495 !important;
  }
}

ul.mb-ul-tree {
  list-style: none !important;
}

.item {
  cursor: pointer;
}

.bold {
  font-weight: bold;
}

ul {
  padding-left: 1em;
  line-height: 1.5em;
  list-style: none;
}

</style>
