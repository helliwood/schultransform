<template>
  <li>
    <div class="d-flex">
      <div
          :class="{bold: this.isFolder}"
          @click="openClose(item)">
        <!-- this shows the name of the folder: verify if the 'root' prop is passed -->
        <template v-if="isFolder">
          <i class="fa fa-folder text-black-50 mr-1"></i><span v-if="root">{{ root }}</span><span
            v-else>{{ key_ }}</span>
        </template>
        <!-- this shows the files but excludes the not allowed keys -->
        <template v-else-if="key_ !== 'id' && key_ !== 'isFolder'"><span
            :class="currentShowItem===item.id?'mb-tree-icon-active':''"
            @click="onshow(item, {type:'file'})"><i
            class="fa fa-eye text-black-50"></i> {{ item.name }}</span>
        </template>

        <!-- this shows the symbols (+,-) and checks it the folder is empty -->
        <template v-if="isFolder">
          <template v-if="!emptyFolder">
            <span v-if="isOpen">[<i class="fa fa-minus mb-icon"></i>]</span>
            <span v-else>[<i class="fa fa-plus mb-icon"></i>]</span>
          </template>
          <template v-if="emptyFolder">
            <span><i class="fad fa-do-not-enter"></i></span>
          </template>
        </template>

      </div>

      <!-- this is the call to action link to load the folder data in the b-table or in the icons div -->
      <div class="ml-2" v-if="isFolder" @click.prevent="loadFolder(item)">
        <small>Laden Folder</small>
      </div>
    </div>

    <ul v-show="isOpen" v-if="isFolder">
      <!-- it built the folder structure: it is an recursive task-->
      <tree-item
          class="item"
          v-for="(child, index) in items"
          :key="index"
          :item="child"
          :key_="index"
          :openclose="openclose"
          :onshow="onshow"
          :loadFolder="loadFolder"
          :current-show-item="currentShowItem"
      ></tree-item>
    </ul>
  </li>
</template>

<script>

export default {
  name: "TreeItem",
  props: {
    root: null,
    rootFolderId: null,
    item: null,
    key_: null,
    onshow: Function,
    loadFolder: Function,
    currentShowItem: null,
    openclose: Function,
  },
  data: function () {
    return {
      isOpen: false,
      items: [],
    };
  },
  mounted() {
    /* For recursion to work you need to assign its value to a variable as the data
    * structure changes and you need to check if a 'children' key exists.
    * */
    if (this.item.children) {
      this.items = this.item.children;
    } else {
      this.items = this.item;
    }
  },
  computed: {
    isFolder: function () {
    /* the data structure contains a key 'isFolder to ensure that there is a folder' */
      if (this.item.isFolder &&
          typeof this.item === 'object' &&
          (Object.keys(this.item).length > 0)
      ) {
        return true
      }

    },
    emptyFolder() {
      let toReturn = false;
      if (this.items) {
        if (this.items.length < 1) {
          toReturn = true;
        }
      }

      return toReturn;
    },
  },
  methods: {

    openClose: function (item) {
      if (this.isFolder) {
        this.openclose(item, {type: 'folder'});
        this.isOpen = !this.isOpen;
      }
    },
  }
}
</script>

<style lang="scss" scoped>
.mb-icon {
  font-size: .8em;
}
</style>