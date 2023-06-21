<template>
  <div class="for-loop-content" v-if="visible">
    <div>
      <div class="d-flex mb-2">
        <span class="text-muted">#{{ loop }}</span>
        <div class="d-flex-column ml-auto">
          <a class="btn btn-sm btn-primary" @click.prevent="add"><i class="fas fa-plus"></i></a>
          <a class="btn btn-sm btn-primary" v-if="!first" @click.prevent="up"><i class="fas fa-angle-up"></i></a>
          <a class="btn btn-sm btn-primary" v-if="!last" @click.prevent="down"><i class="fas fa-angle-down"></i></a>
          <a class="btn btn-sm btn-danger" @click.prevent="remove"><i class="fa fa-trash-alt"></i></a>
        </div>
      </div>
      <slot></slot>
    </div>
  </div>
</template>

<script>
export default {
  name: "for-loop-content",
  props: {
    id: String,
    fullname: String,
    loop: Number,
    value: Object | Array
  },
  data() {
    return {
      visible: !!this.value,
      first: this.loop === 0,
      last: false
    }
  },
  mounted() {
    this.last = (this.loop + 1) >= this.$parent.$children.length;
  },
  methods: {
    add: function () {
      this.$parent.addChildBefore(this.fullname);
    },
    up: function () {
      this.$parent.upChild(this.fullname);
    },
    down: function () {
      this.$parent.downChild(this.fullname);
    },
    remove: function () {
      this.$parent.removeChild(this.fullname);
    },
  }
};
</script>

<style scoped>
.for-loop-content {
  border: 1px solid #ccc;
  padding: 8px;
}

a {
  font-size: 0.6rem !important;
}
</style>