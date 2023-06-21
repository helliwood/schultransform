<template>
  <div class="content-tree-site">
    <transition name="site"
                @enter="enter"
                @after-enter="afterEnter"
                @leave="leave">
      <div v-if="isShow" class="site-row border d-flex align-items-center"
           :class="{'light': index % 2 === 0, 'dark': index % 2 !== 0 }">
        <div class="d-flex flex-grow-1 p-2" @click="toggleOpen">
          <div class="site-icon text-primary text-right" style="width: 18px;" :style="{marginLeft: depth * 14 + 'px'}">
            <i class="fas fa-file-alt mr-1" v-if="site.children.length <=0"></i>
            <i class="fas fa-folder-open mr-1" v-else-if="open"></i>
            <i class="fas fa-folder mr-1" v-else></i>
          </div>
          <div class="site-name font-weight-bold" v-html="siteName"></div>
          <div class="icons mr-auto">
            <span class="ml-1 badge badge-secondary" v-if="site.menuEntry">Menü</span>
            <span class="ml-1 badge badge-secondary" v-if="site.published">Publiziert</span>
            <span class="ml-1 badge badge-danger" v-if="site.deleted">Gelöscht</span>
          </div>
        </div>
        <content-tree-site-actions class="site-options d-flex p-2" :site="site"></content-tree-site-actions>
      </div>
    </transition>
    <content-tree-site
        :show="open && show"
        :filter="filter"
        :index="i+index+1"
        :depth="depth+1"
        :site="site"
        v-for="(site, i) in this.site.children"
        :key="site.id">
    </content-tree-site>
  </div>
</template>

<script>

import ContentTreeSiteActions from "./ContentTreeSiteActions";

export default {
  name: "content-tree-site",
  components: {ContentTreeSiteActions},
  props: {
    show: Boolean,
    index: Number,
    depth: Number,
    filter: String,
    site: Array | Object
  },
  data() {
    return {
      isShow: this.show,
      open: false,
      childrenHasFilterMatch: false,
      filterMode: false,
      siteName: null,
      currentFilter: null
    }
  },
  mounted() {
    this.siteName = this.site.name;
    if (this.$cookies.isKey('contenttree-site-open-' + this.site.id)) {
      this.open = true;
    } else {
      this.open = false;
    }
  },
  watch: {
    filter: {
      handler: 'filterChanged',
      immediate: true,
    },
    show: {
      handler: 'setShow',
      immediate: true,
    },
    site: {
      handler: 'siteUpdated',
      immediate: true,
    }
  },
  methods: {
    toggleOpen(event) {
      this.setOpen(!this.open);
      if (this.open) {
        this.$cookies.set('contenttree-site-open-' + this.site.id);
      } else {
        this.$cookies.remove('contenttree-site-open-' + this.site.id);
      }
    },
    filterChanged(filter) {
      this.currentFilter = filter;
      this.siteName = this.site.name;
      if (!filter) {
        this.filterMode = false;
        this.setChildrenHasFilterMatch(false);
        this.setShow(this.show);
        if (this.$cookies.isKey('contenttree-site-open-' + this.site.id)) {
          this.setOpen(true);
        } else {
          this.setOpen(false);
        }
      } else {
        this.filterMode = true;
        this.childrenHasFilterMatch = false;
        let regex = new RegExp(filter, 'ig');
        if (regex.exec(this.site.name) || this.site.id === parseInt(filter) || this.childrenHasFilterMatch) {
          this.setShow(true);
          this.siteName = this.siteName.replace(regex, function (match) {
            return '<span class="highlight">' + match + '</span>'
          });
          if (this.site.id === parseInt(filter)) {
            this.siteName = this.siteName + ' <span class="badge badge-info">Id Match</span>';
          }
          if (this.$parent.$options.name === "content-tree-site") {
            this.$parent.setChildrenHasFilterMatch(true);
          }
        } else {
          this.setShow(false);
        }
      }
    },
    setShow(show) {
      this.isShow = show;
    },
    setOpen(open) {
      this.open = open;
    },
    setChildrenHasFilterMatch(chfm) {
      this.childrenHasFilterMatch = chfm;
      if (this.filterMode) {
        this.setShow(this.childrenHasFilterMatch);
        if (this.$parent.$options.name === "content-tree-site") {
          this.$parent.setChildrenHasFilterMatch(chfm);
        }
      }
    },
    siteUpdated() {
      this.filterChanged(this.currentFilter);
    },

    enter(element) {
      const width = getComputedStyle(element).width;

      element.style.width = width;
      element.style.position = 'absolute';
      element.style.visibility = 'hidden';
      element.style.height = 'auto';

      const height = getComputedStyle(element).height;

      element.style.width = null;
      element.style.position = null;
      element.style.visibility = null;
      element.style.height = 0;

      // Force repaint to make sure the
      // animation is triggered correctly.
      getComputedStyle(element).height;

      // Trigger the animation.
      // We use `requestAnimationFrame` because we need
      // to make sure the browser has finished
      // painting after setting the `height`
      // to `0` in the line above.
      requestAnimationFrame(() => {
        element.style.height = height;
      });
    },
    afterEnter(element) {
      element.style.height = 'auto';
    },
    leave(element) {
      const height = getComputedStyle(element).height;

      element.style.height = height;

      // Force repaint to make sure the
      // animation is triggered correctly.
      getComputedStyle(element).height;

      requestAnimationFrame(() => {
        element.style.height = 0;
      });
    },
  }
};
</script>

<style scoped lang="scss">

.site-row {
  cursor: default;
  transition: all 0.35s;
}

.site-row:hover {
  border-color: #006495 !important;
  background-color: rgb(198, 220, 247);
}

.light {
  background-color: #FFF;
}

.dark {
  background-color: #f0f0f6;
}

.site-name ::v-deep .highlight {
  background-color: #bbdbec;
}

.site-leave-active,
.site-enter-active {
  transition: all 0.15s ease-in-out;
  opacity: 1;
}

.site-enter,
.site-leave-to {
  height: 0px;
  opacity: 0;
}
</style>