<template>
  <div>
    <a @click="metas" class="btn btn-sm btn-dark mr-1" :class="{disabled: site.deleted}"><i
        class="fas fa-file-signature"></i> Metas</a>
    <b-button-group size="sm" class="mr-1">
      <b-button variant="primary" size="sm" target="_blank" :href="site.previewPath" class="mr-0"><i
          class="fas fa-eye"></i> Vorschau
      </b-button>
      <b-dropdown v-if="site.hasPublishedSite" size="sm" variant="primary" right text="">
        <b-dropdown-item variant="primary" target="_blank" :href="site.url"><i class="fas fa-eye"></i> Live-Ansicht
        </b-dropdown-item>
      </b-dropdown>
    </b-button-group>
    <b-button-group size="sm" class="mr-1">
      <b-button variant="primary" size="sm" :href="site.editPath" class="mr-0" :class="{disabled: site.deleted}"><i
          class="fas fa-edit"></i> Bearbeiten
      </b-button>
      <b-dropdown size="sm" variant="primary" right text="">
        <b-dropdown-item @click="duplicateSite(site)" variant="primary" target="_blank"
                         :class="{disabled: site.historyCount<=0}"><i
            class="far fa-copy"></i> Duplizieren
        </b-dropdown-item>
        <b-dropdown-item @click="history" variant="dark" target="_blank" :class="{disabled: site.historyCount<=0}"><i
            class="fas fa-history"></i> Historie
        </b-dropdown-item>
      </b-dropdown>
    </b-button-group>
    <a @click="publishSite" class="btn btn-sm btn-primary mr-1"
       :class="{disabled: !site.canBePublished, clickable: !site.canBePublished}"><i
        class="fas fa-upload"></i> Publizieren</a>
    <a @click="deleteSite" class="btn btn-sm btn-danger" v-if="!site.deleted"><i class="fas fa-trash-alt"></i></a>
    <a @click="undeleteSite" class="btn btn-sm btn-primary" v-else><i class="fas fa-trash-restore-alt"></i></a>

    <b-sidebar no-enforce-focus ref="sidebar" :id="'metas-'+site.id" title="Metas" width="50%" lazy right
               shadow body-class="border-top" header-class="shadow-sm">
      <iframe v-if="showIframe" width="100%" height="98%" :src="site.metasPath" frameborder="0"></iframe>
    </b-sidebar>

    <b-sidebar no-enforce-focus ref="historySidebar" :id="'history-'+site.id" :title="site.name+' Historie'" width="35%"
               lazy right shadow body-class="border-top" header-class="shadow-sm">
      <data-table class="" sort-by="date"
                  :sort-desc="true"
                  :per-page="10"
                  :api-url="site.historyPath"
                  :fields="[
                    {key: 'date', label: 'Datum', sortable: true,
                      formatter: (value, key, item) => {
                        return moment(value.date).format('DD.MM.YYYY HH:mm')
                      }
                    },
                    {key: 'action', label: 'Aktion', sortable: true},
                    {key: 'user', label: 'Benutzer', sortable: true,
                      formatter: (value, key, item) => {
                        return value.email;
                      }
                    }]"></data-table>
    </b-sidebar>

    <b-modal
        v-model="openModal"
        :title="modalTitle"
        :ok-variant="modalVariant"
        :ok-title="modalOkTitle"
        cancel-variant="secondary"
        cancel-title="Abbruch"
        @ok="modalOkHandler">
      <div class="my-1 d-flex align-items-center">
        <i class="far fa-question-circle fa-2x pr-2"></i>
        <span v-html="modalContent"></span>
      </div>
    </b-modal>
  </div>
</template>

<script>
import * as axios from 'axios';

export default {
  name: "content-tree-site-actions",
  components: {},
  props: {
    site: Object,
    classes: String
  },
  data() {
    return {
      openModal: false,
      modalTitle: '',
      modalContent: '',
      modalVariant: 'success',
      modalOkTitle: 'Ja, löschen',
      modalOkHandler: () => {
      },
      showIframe: false,
      messageListener: null
    }
  },
  mounted() {
    this.messageListener = (event) => {
      switch (event.data.op) {
        case 'metas-saved':
          if (this.site.id == event.data.siteId) {
            this.$refs.sidebar.hide();
            // get time to close sidebar
            setTimeout(() => {
              this.$root.$emit('tf::content-tree::refresh-tree');
            }, 500);
          }
          break;
      }
    };
    window.addEventListener("message", this.messageListener);
    this.$refs.sidebar.$on("shown", () => {
      this.showIframe = true;
    });
  },
  beforeDestroy() {
    window.removeEventListener("message", this.messageListener);
  },
  methods: {
    deleteSite() {
      this.modalVariant = 'danger';
      this.modalTitle = 'Seite löschen';
      this.modalContent = 'Möchten Sie die Seite <strong>' + this.site.name + '</strong> wirklich löschen?';
      if (this.site.children.length > 0) {
        this.modalContent += '<br />Beachten Sie, dass auch alle Unterseiten gelöscht werden!';
      }
      let self = this;
      this.modalOkHandler = () => {
        axios.get(self.site.deletePath).then(result => {
          console.log(result.data);
          self.$root.$emit('tf::content-tree::refresh-tree');
        });
      };
      this.openModal = true;
    },
    undeleteSite() {
      this.modalVariant = 'primary';
      this.modalTitle = 'Seite löschen rückgängig machen';
      this.modalContent = 'Möchten Sie die Seite <strong>' + this.site.name + '</strong> wirklich wiederherstellen?';
      this.modalOkTitle = 'Ja, wiederherstellen'
      let self = this;
      this.modalOkHandler = () => {
        axios.get(self.site.undeletePath).then(result => {
          console.log(result.data);
          self.$root.$emit('tf::content-tree::refresh-tree');
        });
      };
      this.openModal = true;
    },
    publishSite() {
      let self = this;
      axios.get(self.site.publishPath).then(result => {
        console.log(result.data);
        self.$root.$emit('tf::content-tree::refresh-tree');
      });
    },
    metas() {
      this.showIframe = false;
      this.$root.$emit('bv::toggle::collapse', 'metas-' + this.site.id);
    },
    history() {
      this.$root.$emit('bv::toggle::collapse', 'history-' + this.site.id);
    },
    duplicateSite(site) {
      this.$root.$emit('tf::content-tree::duplicate-site', site.id);
    }
  }
};
</script>

<style scoped lang="scss">
a.btn.disabled.clickable {
  pointer-events: auto;
  cursor: pointer;
}
</style>