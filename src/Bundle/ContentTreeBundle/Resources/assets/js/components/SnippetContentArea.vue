<template>
  <div class="area">
    <div class="content">
      <slot></slot>
    </div>
    <div class="backend">
      <b-button class="add_snippet" size="sm" @click.prevent="addSnippet">
        <i class="fa fa-plus"></i> Snippet hinzufügen
      </b-button>
      <b-button v-if="copiedSnippetCanBeInserted" class="add_snippet" size="sm" @click.prevent="pasteSnippet">
        <i class="fas fa-paste"></i> Einfügen
      </b-button>
    </div>
    <b-sidebar class="backend" no-enforce-focus :id="'snippetadd-sidebar-'+this.id" ref="sidebar"
               title="Snippet hinzufügen" width="30%" lazy right shadow>
      <div class="text-center position-absolute spinnerContainer" :hidden="loaded">
        <b-spinner label="Spinning" variant="primary"></b-spinner>
      </div>
      <form @submit.prevent="onSubmit" :disabled="!loaded">
        <div class="m-3">
          <select name="snippetId" class="form-control" required id="dropDown" :disabled="!loaded">
            <option value="">-- Bitte wählen--</option>
            <option v-for="snippet in snippets" :value="snippet.id">{{ snippet.name }}</option>
          </select>
        </div>
        <div class="m-3">
          <button type="submit" class="form-control btn btn-primary" :disabled="!loaded">
            <i class="fa fa-plus"></i> Hinzufügen
          </button>
        </div>
      </form>
    </b-sidebar>
  </div>
</template>

<script>
import Vue from 'vue';
import Snippet from "./Snippet";
import axios from 'axios';
import qs from 'qs';

export default {
  name: "snippet-content-area",
  components: {
    Snippet
  },
  props: {
    allowedGroups: Array,
    area: String,
    siteContent: Object | Array,
    contentPath: String,
    addPath: String,
    snippetsPath: String,
    snippetPastePath: String
  },
  data() {
    return {
      id: null,
      snippets: [],
      loaded: false,
      newContentVue: null,
      onAddSiteContentFn: null,
      onShowAddSiteContentFn: null,
      onEditSiteContentFn: null,
      onRenderContentAreaFn: null,
      onCopySiteContentFn: null,
      childCount: 0,
      addSnippetAfter: null,
      copiedSnippetCanBeInserted: false,
    }
  },
  beforeMount() {
    this.id = this._uid;
  },
  mounted() {
    // Move outside Snippet/Frontend
    this.$el.querySelector('#snippetadd-sidebar-' + this.id).style.zIndex = 10000;
    this.$el.ownerDocument.body.appendChild(this.$el.querySelector('#snippetadd-sidebar-' + this.id));
    // End Move outside Snippet/Frontend
    this.onAddSiteContentFn = (event) => this.hideSidebar(event);
    this.onShowAddSiteContentFn = (event) => this.onShowAddSiteContent(event);
    this.onEditSiteContentFn = (event) => this.hideSidebar(event);
    this.onRenderContentAreaFn = (event) => this.onRenderContentArea(event);
    this.onCopySiteContentFn = (event) => this.onCopySiteContent(event);
    this.$root.eventBus.$on('tf::content-tree::add-site-content', this.onAddSiteContentFn);
    this.$root.eventBus.$on('tf::content-tree::show-add-site-content', this.onShowAddSiteContentFn);
    this.$root.eventBus.$on('tf::content-tree::edit-site-content', this.onEditSiteContentFn);
    this.$root.eventBus.$on('tf::content-tree::render-content-area', this.onRenderContentAreaFn);
    this.$root.eventBus.$on('tf::content-tree::copy-site-content', this.onCopySiteContentFn);
    this.refreshChildCount();
  },
  destroyed() {
    this.$root.eventBus.$off('tf::content-tree::add-site-content', this.onAddSiteContentFn);
    this.$root.eventBus.$off('tf::content-tree::show-add-site-content', this.onShowAddSiteContentFn);
    this.$root.eventBus.$off('tf::content-tree::edit-site-content', this.onEditSiteContentFn);
    this.$root.eventBus.$off('tf::content-tree::render-content-area', this.onRenderContentAreaFn);
    this.$root.eventBus.$off('tf::content-tree::copy-site-content', this.onCopySiteContentFn);
    if (this.newContentVue) {
      this.newContentVue.$destroy();
    }
  },
  methods: {
    addSnippet(event, after = null) {
      console.log(after);
      this.addSnippetAfter = after;
      this.$root.$emit('bv::toggle::collapse', this.$refs.sidebar.id);
      this.$root.eventBus.$emit('tf::content-tree::add-site-content', this.$refs.sidebar.id);
      axios.get(this.snippetsPath).then((result) => {
        this.snippets = result.data;
        this.loaded = true;
      });
    },
    onSubmit(submitEvent) {
      let formData = {};
      for (let element of submitEvent.target.elements) {
        if (element.name) {
          formData[element.name] = element.value;
        }
      }
      formData['after'] = this.addSnippetAfter ? this.addSnippetAfter.id : null;
      this.loaded = false;
      axios.post(this.addPath, qs.stringify(formData)).then((result) => {
        this.$refs.sidebar.hide();
        this.renderContent(result.data);
      }).catch((error) => {
        console.log(error);
      });
    },
    renderContent(newSiteContent = null) {
      let self = this;
      this.reset();
      axios.get(this.contentPath).then((result) => {
        let res = Vue.compile('<div class="content">' + result.data + '</div>');
        this.newContentVue = new Vue({
          render: res.render,
          store: this.$store,
          components: {
            Snippet
          },
          data: {
            eventBus: self.$root.eventBus,
          },
          staticRenderFns: res.staticRenderFns,
          methods: {}
        }).$mount(this.$el.querySelector(".content"));
        this.refreshChildCount();
        if (newSiteContent != null && newSiteContent.form === true) {
          this.$root.eventBus.$emit('tf::content-tree::edit-site-content', newSiteContent.id);
        }
      });
    },
    reset() {
      if (this.newContentVue) {
        this.newContentVue.$destroy();
      } else {
        let childrenToDestroy = [];
        this.$children.forEach((ele) => {
          if (ele.$options.name == 'snippet' || ele.$options.name == 'snippet-content-area') {
            childrenToDestroy.push(ele);
          }
        });
        childrenToDestroy.forEach((ele) => {
          ele.$destroy();
        });
      }
    },
    hideSidebar(siteContentId = null) {
      if (siteContentId === null || siteContentId !== this.$refs.sidebar.id) {
        this.$refs.sidebar.hide();
      }
    },
    onRenderContentArea(data) {
      if ((this.siteContent && this.siteContent.id === data.siteContentId && this.area === data.area) ||
          (this.siteContent == null && data.siteContentId == null)) {
        this.renderContent();
      }
    },
    onShowAddSiteContent(data) {
      if ((this.siteContent && this.siteContent.id === data.siteContentId && this.area === data.after.area) ||
          (this.siteContent == null && data.siteContentId == null)) {
        this.addSnippet(null, data.after);
      }
    },
    onCopySiteContent(event) {
      this.checkCopiedSnippetCanBeInserted();
    },
    checkCopiedSnippetCanBeInserted() {
      const BreakException = {};
      if (this.$root.eventBus.siteContentCopy) {
        try {
          this.allowedGroups.forEach((item) => {
            this.$root.eventBus.siteContentCopy.snippet.groups.forEach((item2) => {
              if (item === item2) {
                this.copiedSnippetCanBeInserted = true;
                throw BreakException;
              }
            });
          });
        } catch (e) {
          if (e !== BreakException) throw e;
        }
      } else {
        this.copiedSnippetCanBeInserted = false;
      }
    },
    pasteSnippet() {
      axios.post(this.snippetPastePath, qs.stringify({siteContentCopyId: this.$root.eventBus.siteContentCopy.id})).then((result) => {
        this.renderContent();
        // Allow only 1 paste
        this.$root.eventBus.siteContentCopy = null;
        this.$root.eventBus.$emit('tf::content-tree::copy-site-content', null);
      });
    },
    refreshChildCount() {
      this.childCount = this.$el.querySelector(".content").childNodes.length;
    }
  }
};
</script>

<style scoped lang="scss">
@import '@core/scss/clearBackend';

.btn-group-sm .btn,
.btn-sm {
  font-size: $btn-font-size-sm;
  padding: $btn-padding-y-sm $btn-padding-x-sm;
}

.add_snippet {
  margin: 4px;
}

.area {
  border: 1px solid transparent;
}

.area:hover {
  border: 1px solid #CCC;
}

.spinnerContainer {
  left: 50%;
  top: 50%;
  z-index: 100;
}
</style>
