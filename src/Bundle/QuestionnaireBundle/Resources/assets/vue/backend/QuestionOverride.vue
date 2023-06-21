<template>
  <div>
    <slot></slot>
    <div>
      <a @click="add" href="javascript:void(0);" class="btn btn-secondary">Hinzufügen</a>
    </div>
  </div>
</template>

<script>
export default {
  name: "question-override",
  data() {
    return {
      choiceCount: 0,
    }
  },
  mounted() {
    this.$el.firstChild.childNodes.forEach((node, i) => {
      this.addButtons(node);
      console.log(node);
      this.choiceCount++;
    });
  },
  methods: {
    add: function () {
      let newIndex = this.getHighestIndex() + 1;
      let template = this.createChild(this.$el.firstChild.dataset.prototype.replace(/__name__/g, newIndex));
      console.log(template);
      this.addButtons(template);
      this.$el.firstChild.appendChild(template);
      this.choiceCount++;
    },
    createChild(html) {
      var child = document.createElement('div');
      child.innerHTML = html;
      return child.firstChild;
    },
    addButtons(el) {
      var container = this.createChild('<div class="ml-auto mr-3"></div>');
      var delButton = this.createChild('<a href="javascript:void(0);" class="btn btn-danger"><i class="fas fa-trash-alt"></i></a>');
      delButton.addEventListener('click', () => {
        el.remove();
        this.choiceCount--;
      });
      container.appendChild(delButton);
      el.appendChild(container);
      this.currentInput = el.querySelector('input[id$="_choice"]')
    },
    getHighestIndex() {
      var highestIndex = 0;
      this.$el.firstChild.childNodes.forEach((node, i) => {
        console.log(node.childNodes[1].firstChild);
        var found = parseInt(node.childNodes[1].firstChild.id.toString().match(/\d+/)[0]);
        if (found > highestIndex) {
          highestIndex = found;
        }
      });
      return highestIndex;
    },
  }
}
</script>

<style scoped>

</style>