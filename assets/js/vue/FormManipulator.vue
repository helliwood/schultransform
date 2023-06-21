<template>
  <div>
    <slot></slot>
  </div>
</template>

<script>
export default {
  name: "form-manipulator",
  props: {
    manipulations: Array
  },
  data() {
    return {}
  },
  mounted() {
    console.log("i'm here", this.$el);
    var self = this;
    this.manipulations.forEach((man) => {
      console.log(man);
      self.$el.querySelectorAll(man.watch).forEach((watch) => {
        self.manipulateMan(man);
        watch.addEventListener("change", function () {
          self.manipulateMan(man);
        });
      });
    });
  },
  methods: {
    manipulateMan: function (man) {
      let elem = this.$el.querySelector(man.watch);
      let value;
      if (elem.type.toLowerCase() === 'radio') {
        value = self.$el.querySelector(man.watch + ':checked') ? self.$el.querySelector(man.watch + ':checked').value : null;
      } else if (elem.type.toLowerCase() === 'checkbox') {
        value = elem.checked ? 'checked' : 'unchecked';
      } else {
        value = elem.value;
      }
      if (man.if[value]) {
        if (Array.isArray(man.if[value])) {
          man.if[value].forEach(item => {
            this.$el.querySelectorAll(item.elem).forEach((elem) => {
              this.manipulateElem(elem, item);
            });
          });
        } else {
          this.$el.querySelectorAll(man.if[value].elem).forEach((elem) => {
            this.manipulateElem(elem, man.if[value]);
          });
        }
      }
    },
    manipulateElem: function (elem, option) {
      if (option.style) {
        for (let style in option.style) {
          elem.style[style] = option.style[style];
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
