<template>
  <div class="content-tree-site-form-parent" v-if="loaded">
    <v-select :id="id"
              :name="name"
              label="displayName"
              class="mb-2"
              placeholder="-- Hauptebene --"
              :options="items"
              @input="onChange"
              :value="selectedItem"
              :reduce="item => item.id">
      <template #option="{ id, displayName2 }">
        <div style="white-space: pre-wrap;">{{ displayName2 }}</div>
      </template>
      <template #selected-option="{ id, displayName2 }">
        <div style="white-space: pre-wrap;">{{ displayName2 }}</div>
      </template>
    </v-select>
    <v-select class="mb-2"
              label="label"
              :options="children"
              :value="pos"
              @input="onChangePosition"
              :reduce="item => item.position">
    </v-select>
    <input type="hidden" :id="id" :name="name"
           v-model="selectedItem"/>
    <input type="hidden" :id="id.replace(/parent/, 'position')"
           :name="name.replace(/parent/, 'position')"
           v-model="pos"/>
  </div>
</template>

<script>
import * as axios from "axios";

export default {
  name: "content-tree-site-form-parent",
  components: {},
  props: {
    treePath: String,
    siteId: Number,
    id: String,
    name: String,
    value: Number,
    position: Number
  },
  data() {
    return {
      loaded: false,
      tree: null,
      items: [],
      selectedItem: {
        default: null,
        type: Number
      },
      pos: {
        default: 1,
        type: Number
      },
      children: []
    }
  },
  mounted() {
    this.selectedItem = this.value;
    this.pos = this.position;
    this.load();
  },
  methods: {
    load() {
      axios.get(this.treePath).then(result => {
        this.tree = result.data;
        this.populateItems(this.tree);
        this.populateChildren(this.tree, true);
        this.loaded = true;
      });
    },
    populateItems(items, depth = 0) {
      items.forEach((item) => {
        if (item.id !== this.siteId) {
          item.displayName = item.name.padStart(item.name.length + depth * 12, '&nbsp;');
          item.displayName2 = item.name.padStart(item.name.length + depth * 2, '  ');
          //console.log(':' + item.displayName2 + ' / ' + item.id + '/ pos: ' + item.position + ':');
          this.items.push(item);
          if (item.children.length > 0) {
            this.populateItems(item.children, depth + 1);
          }
        }
      });
    },
    onChange(value) {
      this.selectedItem = value;
      this.populateChildren(this.tree, true);

      // select last item
      if (this.children[this.children.length - 1]) {
        this.pos = this.children[this.children.length - 1].position;
      }
    },
    onChangePosition(newPos) {
      //console.log(newPos, this.pos);
      this.pos = newPos;
    },
    populateChildren(items, first = false) {
      if (first) {
        this.children = [{position: 1, label: "An den Anfang"}];
      }
      if (this.selectedItem === null) {
        items.forEach((item) => {
          this.children.push({position: item.position + 1, label: "Nach " + item.name});
        });
        return;
      }
      items.forEach((item) => {
        if (item.id === this.selectedItem) {
          item.children.forEach((item) => {
            if (item.id !== this.siteId) {
              this.children.push({position: item.position + 1, label: "Nach " + item.name});
            }
          });
        } else if (item.children.length > 0) {
          this.populateChildren(item.children);
        }
      });
      if (first) {
        //this.children.push({position: this.children.length + 1, label: "An das Ende"});
      }
    },
    prepend(value, array) {
      var newArray = array.slice();
      newArray.unshift(value);
      return newArray;
    }
  }
};
</script>

<style scoped lang="scss">

</style>