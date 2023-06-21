<template>
  <div class="form-group row">
    <label :for="id" class="col-form-label col-sm-2 required">{{ label }}</label>
    <div :class="colClass">
      <div class="input-group">
        <div v-if="currentValue!=''" class="input-group-prepend imgPrepend">
          <div v-if="currentEntity && currentEntity.mimeType.match(/image/) && !currentEntity.mimeType.match(/svg/)" class="h-100 d-flex align-items-center">
            <div style="width: 30px;" class="h-100 d-flex align-items-center justify-content-center">
              <img :src="'/Backend/MediaBase/show/' + currentValue + '/80x30' "/>
            </div>
          </div>
          <div v-else-if="tempIcon" style="width:30px;" class="h-100 d-flex align-items-center justify-content-center TEST">
            <i :class="tempIcon" class="fa-2x"></i>
          </div>
        </div>
        <b-form-input class="d-none" v-model="currentValue" placeholder="Datei auswählen"></b-form-input>
        <div @click="edit" class="fileInfo d-flex align-items-center text-truncate">
          <div v-html="currentLabel"></div>
          <div v-if="currentEntity" class="details d-flex pl-2 flex-column ml-auto">
            <div class="mt-auto" v-if="currentEntity.formatFileSize">Größe: {{ currentEntity.formatFileSize }}</div>
            <div class="mb-auto" v-if="currentEntity.extension">Endung: {{ currentEntity.extension }}</div>
          </div>
          <div v-else class="novalue d-flex align-items-center justify-content-center">
            Keine Datei gewählt
          </div>
        </div>
        <div class="input-group-append">
          <b-button @click="edit" variant="primary">Wählen</b-button>
        </div>
        <div v-if="currentValue != ''" class="input-group-append">
          <b-button variant="danger" @click="clear" title="Löschen"><i class="fas fa-backspace"></i></b-button>
        </div>
      </div>
    </div>
    <b-sidebar
        ref="sidebar"
        title="Medienparser"
        width="95%"
        v-model="showSidebar"
        lazy right shadow backdrop>
      <div class="px-2 pl-3">
        <div class="panel">
          <media-browser
              api-url="/Backend/MediaBase"
              :form-element-call="true"
              :startFolder="parent"
              :parse="parse"
              :parseCallback="parseCallback"
              :filetype="filetype"
          ></media-browser>
        </div>
      </div>
    </b-sidebar>
    <slot></slot>
  </div>
</template>

<script>
import axios from 'axios'
import qs from 'qs';

export default {
  name: "media-form-element",
  props: {
    id: String,
    name: String,
    label: String,
    colClass: String,
    value: null,
    filetype: null,
    apiUrl: String,
    entity: null,
    parent: null,
    icon: null
  },
  data() {
    return {
      currentEntity: this.entity,
      currentValue: this.value,
      currentLabel: '',
      showSidebar: false,
      parse: true,
      tempIcon:this.icon,
    }
  },
  mounted() {
    this.setLabel();
  },
  methods: {
    parseCallback(item, icon) {
      this.currentEntity = item;
      this.tempIcon = icon;
      this.$el.querySelector('#' + this.id).value = item.id;
      this.currentValue = item.id;
      this.setLabel();
      this.showSidebar = false;
    },
    apiCall(data, url = null) {
      let self = this;

      let promise = axios.post(url ? url : (this.apiUrl ? this.apiUrl : ''), qs.stringify(data), {
        headers: {
          'Content-Type':
              'application/x-www-form-urlencoded'
        }
      });
      promise.then((data) => {
      }).catch(error => {
      });
    },
    setLabel() {
      if (this.currentEntity) {
        this.currentLabel = this.currentEntity.name;
      } else {
        this.currentLabel = "";
      }
    },
    edit() {
      this.showSidebar = true;
    },
    clear() {
      this.currentValue = "";
      this.currentEntity = null;
      this.$el.querySelector('#' + this.id).value = '';
      this.setLabel();
    }
  }
}

</script>

<style scoped lang="scss">
@import '@core/scss/clearBackend';

.imgPrepend {
  height: 33px;
  min-width: 33px;
  border-top-left-radius: $input-border-radius;
  border-bottom-left-radius: $input-border-radius;
  border: $input-border-width solid $input-border-color;
  border-right: 0px;
  padding: 0 $grid-gutter-width;
  background-color: $white;
}

.fileInfo {
  position: relative;
  flex: 1 1 auto;
  width: 1%;
  min-width: 0;
  margin-bottom: 0;
  border: $input-border-width solid $input-border-color;
  background-color: $gray-200;
  padding-left: $grid-gutter-width / 2;

  div {
    display: inline-block;
  }

  .details {
    height: 100%;
    background-color: $gray-300;
    font-size: 70%;
    padding: 0 $grid-gutter-width / 2;
  }
}

.fileInfo:hover {
  cursor: pointer;
  background-color: $hover-color;

  .details {
    opacity: 0.8;
  }
}

.novalue {
  font-style: italic;
  opacity: 0.8;
}

</style>