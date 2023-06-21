<template>
  <div class="snippet-link">
    <slot></slot>
  </div>
</template>

<script>

export default {
  name: "snippet-link",
  components: {},
  props: {
    id: String,
    treePath: String,
  },
  data() {
    return {
      loaded: false,
      tree: null,
      intern: true,
      elementType: null,
      elementLinkIntern: null,
      elementLinkExtern: null
    }
  },
  mounted() {
    this.elementType = this.$el.querySelector('#' + this.id + '_type');
    this.elementType.style.borderTopRightRadius = 0;
    this.elementType.style.borderBottomRightRadius = 0;
    this.elementLinkIntern = this.$el.querySelector('#' + this.id + '_link_intern');
    this.elementLinkExtern = this.$el.querySelector('#' + this.id + '_link_extern');
    this.update();
    this.elementType.addEventListener("change", (event) => {
      this.update();
    });
  },
  methods: {
    update() {
      this.intern = this.elementType.value === 'intern';
      if (this.intern) {
        this.elementLinkIntern.style.display = '';
        this.elementLinkExtern.style.display = 'none';
        if (this.elementLinkIntern.nextElementSibling)
          this.elementLinkIntern.parentElement.insertBefore(this.elementLinkIntern.nextElementSibling, this.elementLinkIntern);
      } else {
        this.elementLinkIntern.style.display = 'none';
        this.elementLinkExtern.style.display = '';
        if (this.elementLinkExtern.nextElementSibling)
          this.elementLinkExtern.parentElement.insertBefore(this.elementLinkExtern.nextElementSibling, this.elementLinkExtern);
      }
    }
  }
};
</script>

<style scoped lang="scss">

</style>