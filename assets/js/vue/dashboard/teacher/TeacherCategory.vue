<template>
  <div>
    <div class="mb-4">
      <div class="mt-1 mb-2 tr-line-modal">
        <p>{{ title }}</p>
      </div>

      <p>{{ intro }}</p>


      <div v-for="item in this.data" class="d-flex flex-column">
        <div class="questionaire-overlay-wrapper d-flex">
          <div :class="'questionaire-overlay-icon bg-category-'+category ">
            <i :class="'text-white fad '+item.icon"></i>
          </div>
          <div v-if="item.doneat" class="col pl-1 pr-0 tr-md-w-10 w-md-100">
            <div :class="'pl-1  category-'+category+' border-category-'+category">
              <p>
              <span class="tr-ellipsis">
            {{ item.name }}
              </span>
              </p>
            </div>
          </div>
          <div v-else class="col pl-1 pr-0 tr-sm-w-10 w-sm-100">
            <div :class="'pl-1 border-category-'+category">
              <p>
              <span class="tr-ellipsis">
            {{ item.name }}
              </span>
              </p>
            </div>
          </div>
          <a :href="item.slug" class="questionaire-overlay-button">
            <span v-if="item.doneat" :class="'d-block border-category-'+category">Wiederholen</span>
            <span v-if="!item.doneat" :class="'d-block border-category-'+category+' category-'+category">Starten</span>
          </a>
        </div>
        <div>
          <span v-if="item.doneat" class="smaller-font d-block pb-1 light-subtitle-color">Stand: {{ item.doneat }}</span>
          <span v-else class="smaller-font d-block pb-1 light-subtitle-color">noch nicht gestartet</span>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
export default {
  name: "TeacherCategory",
  props: {
    data: null,
    id: null,
    category: null,
    texts: null,
    intro: null,
    title: null
  },
  mounted() {

    if (this.texts === null) {
      this.texts = ['starten', 'fortfahren', 'noch nicht gestartet']
    }
  },
  computed: {
    getCode() {
      if (this.data && (this.data.code)) {
        return this.data.code;
      }
    }
  },
}
</script>

<style scoped>

</style>
