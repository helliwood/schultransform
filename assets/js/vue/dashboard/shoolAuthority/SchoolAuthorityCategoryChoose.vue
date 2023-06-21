<template>
  <div>
    <div class="mb-4">
      <div class="mt-1 mb-2 tr-line-modal">
        <p>{{ title }}</p>
      </div>

      <p>{{ intro }}</p>

      <div v-for="item in this.data" class="d-flex flex-column">
        <div class="questionaire-overlay-wrapper d-flex" :class="'c-'+item.id">
          <div class="tr-name-bg" :class="'questionaire-overlay-icon'">
            <i :class="'text-white fad '+item.icon"></i>
          </div>
          <div v-if="item.numberOfQuestionnairesFilledOut >= 1" class="col pl-1 pr-0 tr-md-w-10 w-md-100">
            <div class="pl-1 tr-name-bg text-white tr-border">
              <p>
              <span class="tr-ellipsis">
            {{ item.name }}
              </span>
              </p>
            </div>
          </div>
          <div v-else class="col pl-1 pr-0 tr-sm-w-10 w-sm-100">
            <div class="pl-1 tr-border">
              <p>
              <span class="tr-ellipsis">
            {{ item.name }}
              </span>
              </p>
            </div>
          </div>
          <a :href="item.questionnaire.slug"
             class="tr-border-radius-right px-2"
          :class="item.numberOfQuestionnairesFilledOut >= 1?'tr-border':'tr-name-bg text-white'">
              <span v-if="item.numberOfQuestionnairesFilledOut >= 1" class="tr-color-black-100 tr-text-decoration">Wiederholen</span>
              <span v-else>Starten</span>
          </a>
        </div>
        <div>
          <span v-if="item.questionnaire.doneat" class="smaller-font d-block pb-1 light-subtitle-color">Stand: {{
              item.questionnaire.doneat
            }}</span>
          <span v-else class="smaller-font d-block pb-1 light-subtitle-color">noch nicht gestartet</span>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
export default {
  name: "SchoolAuthorityCategoryChoose",
  props: {
    data: null,
    id: null,
    category: null,
    texts: null,
    intro: null,
    title: null
  },
  mounted() {
    console.log('data', this.data);
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
