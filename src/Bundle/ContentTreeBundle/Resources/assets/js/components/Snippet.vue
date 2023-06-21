<template>
  <div class="snippet">
    <div class="snippet_content tf_frontend">
      <slot></slot>
    </div>
    <b-button-group size="sm" :class="siteContent.form ? 'edit_snippet' : 'edit_snippet_without_form'" class="backend">
      <b-button @click="edit" v-if="siteContent.form"><i class="fa fa-edit"></i></b-button>
      <b-dropdown :right="siteContent.form" text="" size="sm" @hide="hideDropDownMenu">
        <b-dropdown-text text-class="d-inline-flex align-items-center">
          <span class="pr-2">{{ siteContent.snippet.name }}</span>
          <span class="badge badge-info ml-auto">{{ siteContent.snippet.id }}</span>
        </b-dropdown-text>
        <b-dropdown-divider></b-dropdown-divider>
        <b-dropdown-item @click="copySnippetId"><i class="fa fa-copy"></i> Id <strong>{{siteContent.id}}</strong>
          kopieren
        </b-dropdown-item>
        <b-dropdown-item @click="addSnippet"><i class="fa fa-plus"></i> Snippet hinzufügen</b-dropdown-item>
        <b-dropdown-item @click="edit" v-if="siteContent.form"><i class="fa fa-edit"></i> Bearbeiten</b-dropdown-item>
        <b-dropdown-item @click="copy"><i class="far fa-copy"></i> Kopieren</b-dropdown-item>
        <b-dropdown-item @click="up" v-if="this.siteContent.position > 1"><i class="fas fa-chevron-up"></i> Hoch
        </b-dropdown-item>
        <b-dropdown-item @click="down" v-if="this.siteContent.totalItems > this.siteContent.position"><i
            class="fas fa-chevron-down"></i> Runter
        </b-dropdown-item>
        <b-dropdown-divider></b-dropdown-divider>
        <b-dropdown-text>
          <b-button variant="danger" size="sm" block @click="deleteSnippet">
            <i class="fa fa-trash-alt"></i> {{ deleteConfirmed ? 'Sicher?' : 'Löschen' }}
          </b-button>
        </b-dropdown-text>
      </b-dropdown>
    </b-button-group>

    <b-sidebar class="backend" no-enforce-focus ref="sidebar" :id="'snippetedit-sidebar-'+siteContent.id"
               :title="siteContent.snippet.name+' ('+siteContent.snippet.id+') bearbeiten'" width="50%" lazy right shadow
               body-class="border-top" header-class="shadow-sm">
      <div class="text-center position-absolute spinnerContainer" :hidden="loaded">
        <b-spinner label="Spinning" variant="primary"></b-spinner>
      </div>
      <div class="snippetFormContainer" :class="loaded ? '' : 'loading'">
        <div id="snippetFormContainer">
        </div>
      </div>
    </b-sidebar>
  </div>
</template>

<script>
import Vue from 'vue';
import axios from 'axios';
import qs from 'qs';
import ForLoop from "./ForLoop";
import ForLoopContent from "./ForLoopContent";
import SnippetContentArea from "./SnippetContentArea";
import {setResizeListeners} from "../helpers/auto-resize";

export default {
  name: "snippet",
  components: {},
  props: {
    snippetContentPath: String,
    snippetFormPath: String,
    snippetDeletePath: String,
    snippetUpPath: String,
    snippetDownPath: String,
    snippetCopyPath: String,
    siteContent: Object | Array
  },
  data() {
    return {
      form: null,
      loaded: false,
      deleteConfirmed: false,
      inEditMode: false,
      onAddSiteContentFn: null,
      onEditSiteContentFn: null
    }
  },
  mounted() {
    this.onAddSiteContentFn = (id) => this.onAddSiteContent(id);
    this.onEditSiteContentFn = (id) => this.onEditSiteContent(id);
    this.$root.eventBus.$on('tf::content-tree::add-site-content', this.onAddSiteContentFn);
    this.$root.eventBus.$on('tf::content-tree::edit-site-content', this.onEditSiteContentFn);
    this.$mount('#app');
  },
  beforeDestroy() {
    this.$root.eventBus.$off('tf::content-tree::add-site-content', this.onAddSiteContentFn);
    this.$root.eventBus.$off('tf::content-tree::edit-site-content', this.onEditSiteContentFn);
  },
  methods: {
    edit() {
      this.inEditMode = true;
      // Move outside Snippet/Frontend
      this.$el.ownerDocument.body.querySelector('#snippetedit-sidebar-' + this.siteContent.id).style.zIndex = 10000;
      this.$el.ownerDocument.body.querySelector('#app').appendChild(this.$el.ownerDocument.body.querySelector('#snippetedit-sidebar-' + this.siteContent.id));
      // END Move outside Snippet/Frontend
      this.$root.$emit('bv::toggle::collapse', 'snippetedit-sidebar-' + this.siteContent.id);
      this.$root.eventBus.$emit('tf::content-tree::edit-site-content', this.siteContent.id);
      let self = this;
      this.loaded = false;
      axios.get(this.snippetFormPath).then((result) => {
        self.renderForm(result.data);
        self.loaded = true;
      });
    },
    onSubmit(submitEvent) {
      let formData = {};
      for (let element of submitEvent.target.elements) {
        formData[element.name] = element.value;
      }
      let self = this;
      this.loaded = false;
      axios.post(submitEvent.target.action, qs.stringify(formData)).then((result) => {
        self.renderForm(result.data);
        self.renderSnippet();
        self.loaded = true;
        if (submitEvent.submitter.value == 'save_and_close') {
          self.$refs.sidebar.hide();
          self.inEditMode = false;
        }
      }).catch((error) => {
        console.log(error);
      });
    },
    renderForm(data) {
      let self = this;
      let res = Vue.compile('<div id="snippetFormContainer">' + data.replace(/<form/, '<form @submit.prevent="submit"') + '</div>');
      this.form = new Vue({
        render: res.render,
        store: this.$store,
        components: {
          ForLoop,
          ForLoopContent
        },
        data: {
          eventBus: self.eventBus,
        },
        staticRenderFns: res.staticRenderFns,
        methods: {
          submit: self.onSubmit
        },
        mounted() {
          setResizeListeners(this.$el, ".js-autoresize");
        }
      }).$mount(this.$el.ownerDocument.body.querySelector("#snippetFormContainer"));
    },
    renderSnippet() {
      let self = this;
      axios.get(this.snippetContentPath).then((result) => {
        let res = Vue.compile('<div class="snippet_content tf_frontend">' + result.data + '</div>');
        this.newContentVue = new Vue({
          render: res.render,
          store: this.$store,
          components: {
            SnippetContentArea
          },
          data: {
            eventBus: self.$root.eventBus,
          },
          staticRenderFns: res.staticRenderFns,
          methods: {}
        }).$mount(this.$el.querySelector(".snippet_content"));
        //this.$el.querySelector(".snippet_content").innerHTML = result.data;
      });
    },
    copySnippetId() {
      function fallbackCopyTextToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;

        // Avoid scrolling to bottom
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";

        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
          var successful = document.execCommand('copy');
          var msg = successful ? 'successful' : 'unsuccessful';
          console.log('Fallback: Copying text command was ' + msg);
        } catch (err) {
          console.error('Fallback: Oops, unable to copy', err);
        }

        document.body.removeChild(textArea);
      }

      function copyTextToClipboard(text) {
        if (!navigator.clipboard) {
          fallbackCopyTextToClipboard(text);
          return;
        }
        navigator.clipboard.writeText(text).then(function () {
          console.log('Async: Copying to clipboard was successful!');
        }, function (err) {
          console.error('Async: Could not copy text: ', err);
        });
      }

      copyTextToClipboard(this.siteContent.id);
    },
    addSnippet() {
      this.$root.eventBus.$emit('tf::content-tree::show-add-site-content', {
        after: this.siteContent,
        siteContentId: this.siteContent.parent ? this.siteContent.parent.id : null
      });
    },
    deleteSnippet(event) {
      if (this.deleteConfirmed) {
        event.target.innerHTML = "wird gelöscht..."
        event.target.disabled = true;
        axios.get(this.snippetDeletePath).then((result) => {
          this.$root.eventBus.$emit('tf::content-tree::render-content-area', {
            siteContentId: this.siteContent.parent ? this.siteContent.parent.id : null,
            area: this.siteContent.area
          });
        });
      } else {
        this.deleteConfirmed = true;
      }
    },
    up(event) {
      axios.get(this.snippetUpPath).then((result) => {
        if (result.error) {
          console.error(result.error);
        } else {
          this.$root.eventBus.$emit('tf::content-tree::render-content-area', {
            siteContentId: this.siteContent.parent ? this.siteContent.parent.id : null,
            area: this.siteContent.area
          });
        }
      });
    },
    down(event) {
      axios.get(this.snippetDownPath).then((result) => {
        if (result.error) {
          console.error(result.error);
        } else {
          this.$root.eventBus.$emit('tf::content-tree::render-content-area', {
            siteContentId: this.siteContent.parent ? this.siteContent.parent.id : null,
            area: this.siteContent.area
          });
        }
      });
    },
    copy(event) {
      axios.get(this.snippetCopyPath).then((result) => {
        if (result.data.error) {
          console.error(result.data.error);
        } else {
          this.$root.eventBus.siteContentCopy = result.data;
          this.$root.eventBus.$emit('tf::content-tree::copy-site-content', result.data);
        }
      });
    },
    onAddSiteContent(siteContentId = null) {
      this.$refs.sidebar.hide();
    },
    onEditSiteContent(siteContentId = null) {
      if (siteContentId !== null) {
        if (siteContentId === this.siteContent.id && !this.inEditMode) {
          this.edit();
        } else if (siteContentId !== this.siteContent.id && this.inEditMode) {
          this.$refs.sidebar.hide();
          this.inEditMode = false;
        }
      } else {
        this.$refs.sidebar.hide();
      }
    },
    hideDropDownMenu() {
      this.deleteConfirmed = false;
    },
  }
};
</script>

<style scoped lang="scss">
@import '@core/scss/clearBackend';

.snippet .btn-group-sm .btn {
  font-size: $btn-font-size-sm;
  padding: $btn-padding-y-sm $btn-padding-x-sm;
}

.snippet {
  position: relative;
  min-height: 1px;
}

.snippet:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.snippet .snippet_content:hover {
  background-color: white;
}

.edit_snippet {
  position: absolute !important;
  top: 0px !important;
  right: 0px !important;
  margin: 4px !important;
  z-index: 50;
}

.edit_snippet > .btn:first-child {
  border-top-left-radius: 0.25rem;
  border-bottom-left-radius: 0.25rem;
}

.edit_snippet_without_form {
  display: none !important;
  margin: 4px;
}

.snippet:hover > .edit_snippet_without_form {
  display: block !important;
  position: absolute;
  top: 0px;
  left: 0px;
}

.snippetFormContainer {
  padding: 15px;
}

.snippetFormContainer.loading {
  opacity: 0.5;
}

.spinnerContainer {
  left: 50%;
  top: 50%;
  z-index: 100;
}
</style>
