<template>
  <div v-if="id">
    <b-modal
        no-close-on-backdrop
        no-close-on-esc
        hide-footer
        size="lg"
        header-class="st-dashboard-modal-header"
        content-class="st-dashboard-modal-wrapper"
        :id="id">

      <template #modal-header="{ close }">
        <div class="d-flex justify-content-between align-items-center tr-bg-black-15 w-100">

          <span class="flex-grow-1 text-center logo d-none d-md-block color-switch">schul<span>transform</span>.</span>
          <span class="flex-grow-1 text-center logo d-block d-md-none color-switch">s<span>t.</span></span>

          <b-button @click="close()" class="border border-primary pt-1" variant="primary"><i
              class="text-white fad fa-times tr-display-3"></i></b-button>

        </div>
      </template>

      <div class="px-3 pt-3" v-if="modalComponentName && (componentType[modalComponentName])">

        <component :is="componentType[modalComponentName]" :texts="texts" :title="title" :intro="intro" :data="data"
                   :category="category"></component>

      </div>
      <div class="st-dashboard-modal-footer mt-4">
        <p class="tr-border-radius-bottom bg-primary st-line-bottom"></p>
      </div>
    </b-modal>
  </div>
</template>

<script>


import CodeSend from "./teacher/CodeSend";
import TeacherCategory from "./teacher/TeacherCategory";
import TeacherQuestionairs from "./teacher/TeacherQuestionairs";
import SchoolCategory from "./school/SchoolCategory";
import TeacherCodeOnly from "./teacher/TeacherCodeOnly";
import SchoolAuthorityCategoryChoose from "./shoolAuthority/SchoolAuthorityCategoryChoose"
import SchoolAuthorityCode from "./shoolAuthority/SchoolAuthorityCode"
import SchoolAuthorityRequestInfoSchool from "./shoolAuthority/SchoolAuthorityRequestInfoSchool"


export default {
  name: "dashboard-modal",
  components: {
    CodeSend,
    TeacherCategory,
    TeacherQuestionairs,
    SchoolCategory,
    TeacherCodeOnly,
    SchoolAuthorityCategoryChoose,
    SchoolAuthorityCode,
    SchoolAuthorityRequestInfoSchool,
  },
  props: {
    modalComponentName: null,
    data: null,
    id: null,
    category: null,
    texts: null,
    intro: null,
    title: null,
  },
  data() {
    return {
      componentType: {
        'teacherCodeSend': CodeSend,
        'teacherCategory': TeacherCategory,
        'TeacherQuestionairs': TeacherQuestionairs,
        'teacherCodeOnly': TeacherCodeOnly,
        'SchoolCategory': SchoolCategory,
        'SchoolAuthorityCategoryChoose': SchoolAuthorityCategoryChoose,
        'SchoolAuthorityCode': SchoolAuthorityCode,
        'SchoolAuthorityRequestInfoSchool': SchoolAuthorityRequestInfoSchool,
      },
    }
  },
  destroyed() {
    this.data = null;
  },
  computed: {},
}
</script>

<style lang="scss">
.st-dashboard-modal-header {
  padding: 0 !important;
}

.st-dashboard-modal-wrapper {
  border-radius: 0 0 8px 8px !important;

  .modal-body {
    padding: 0;
  }

  .st-dashboard-modal-body {
    padding: 1.2em;
  }

  .st-dashboard-modal-footer {
    position: relative;

    .st-line-bottom {
      height: 10px;
      position: absolute;
      bottom: -8px;
      z-index: 10;
      left: 0;
      right: 0;
    }
  }
}
</style>
