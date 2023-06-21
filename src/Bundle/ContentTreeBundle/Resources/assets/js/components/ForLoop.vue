<template>
  <div class="for-loop">
    {{ label }}:
    <div ref="childs">
      <slot></slot>
    </div>
    <!--    <template v-for="(item, index) in children">-->
    <!--      <for-loop-content :value="{}" :loop="index"><slot></slot></for-loop-content>-->
    <!--    </template>-->
    <a class="btn btn-primary mt-2" @click.prevent="add">Hinzufügen</a>
  </div>
</template>

<script>
import ForLoopContent from "./ForLoopContent";

export default {
  name: "for-loop",
  components: {
    ForLoopContent
  },
  props: {
    id: String,
    fullname: String,
    label: String,
    value: Object | Array
  },
  data() {
    return {}
  },
  mounted() {
  },
  methods: {
    add() {
      //if(this.$children.length === 1 )
      if (!this.$children[0].visible) {
        this.$children[0].visible = true;
      } else {
        this.$root.$refs.snippet_edit.querySelector('input#TemplateEngine_Snippet_TE-action-TE').value = "append";
        this.$root.$refs.snippet_edit.querySelector('input#TemplateEngine_Snippet_TE-action_value-TE').value = this.fullname;
        this.$root.$refs.snippet_edit.querySelector('button[value=save]').click();
      }
    },
    addChildBefore(id) {
      this.$root.$refs.snippet_edit.querySelector('input#TemplateEngine_Snippet_TE-action-TE').value = "addBefore";
      this.$root.$refs.snippet_edit.querySelector('input#TemplateEngine_Snippet_TE-action_value-TE').value = id;
      this.$root.$refs.snippet_edit.querySelector('button[value=save]').click();
    },
    upChild(id) {
      this.$root.$refs.snippet_edit.querySelector('input#TemplateEngine_Snippet_TE-action-TE').value = "up";
      this.$root.$refs.snippet_edit.querySelector('input#TemplateEngine_Snippet_TE-action_value-TE').value = id;
      this.$root.$refs.snippet_edit.querySelector('button[value=save]').click();
    },
    downChild(id) {
      this.$root.$refs.snippet_edit.querySelector('input#TemplateEngine_Snippet_TE-action-TE').value = "down";
      this.$root.$refs.snippet_edit.querySelector('input#TemplateEngine_Snippet_TE-action_value-TE').value = id;
      this.$root.$refs.snippet_edit.querySelector('button[value=save]').click();
    },
    removeChild(id) {
      this.$root.$refs.snippet_edit.querySelector('input#TemplateEngine_Snippet_TE-action-TE').value = "remove";
      this.$root.$refs.snippet_edit.querySelector('input#TemplateEngine_Snippet_TE-action_value-TE').value = id;
      this.$root.$refs.snippet_edit.querySelector('button[value=save]').click();
    }
  }
};
</script>

<style scoped lang="scss">
@import "@core/scss/backend";

.for-loop {
  border: 1px solid $primary;
  padding: 8px;
  margin-bottom: 16px;
  border-radius: 6px;
}

a {
  font-size: 0.6rem !important;
}
</style>
