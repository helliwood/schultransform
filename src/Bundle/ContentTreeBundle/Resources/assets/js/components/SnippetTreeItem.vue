<template>
  <ul>
    <li><strong>{{ item.name }}</strong> <span class="text-muted">(Id: {{ item.id }} | Snippet-Id: {{
        item.snippet.id
      }})</span>
      <ul>
        <li v-for="(area, areaName) in item.children">Content-Bereich: <strong>{{ areaName }}</strong>
          <snippet-tree-item :item="child" v-for="child in area" :key="child.id"></snippet-tree-item>
        </li>
      </ul>
    </li>
  </ul>
</template>

<script>

export default {
  name: "snippet-tree-item",
  components: {},
  props: {
    item: Object
  },
  mounted() {
  }
};
</script>

<style scoped lang="scss">
ul {
  font-size: 0.7rem;
  padding-left: 20px;
  margin-bottom: 5px;
}
</style>