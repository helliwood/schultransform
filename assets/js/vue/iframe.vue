<template>
  <iframe :id="$data._id">
    <slot></slot>
  </iframe>
</template>

<script>
  export default {
    name: 'frame-container',
    props: {
      id: String,
      finishUrl: String,
    },
    data() {
      return {
        _id: this.id? this.id:"noId_"+Math.floor(Math.random() * Math.floor(999999)),
        _frameborder: this.frameborder? this.frameborder:0,
        _finishUrl: this.finishUrl? this.finishUrl!=undefined? this.finishUrl:'/auswertung' :'/auswertung'
      }
    },
    mounted() {

      window.addEventListener("message", (event) => {
        if(event.data.op == "frame-finish" && event.data.id == this.$data._id) {
          window.location.href = this.$data._finishUrl;
        }
      }, false);
    }
  }
</script>

<style lang="scss"></style>