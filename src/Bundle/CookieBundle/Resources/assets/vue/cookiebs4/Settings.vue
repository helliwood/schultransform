<template>

  <div v-if="data" class="tr-cookie-wrapper-settings">
    <!--Content-->
    <p class="tr-cookie-p-content" v-if="data.contentsub" v-html="data.contentsub"></p>

    <!--Cookies-->
    <section v-if="data.item" v-for="(cookie, index) in data.item"
             class="d-flex flex-column border-bottom mb-2 pb-2 px-1 px-sm-0 tr-cookie-seccion"
    >
      <div class="d-flex flex-column flex-sm-row px-2" :class="cookie.highlight?'border border-primary py-2':''">
        <!--Icon-->
        <div class="coo-w-10 ml-0 ml-sm-3 mr-1">
          <i :class="cookie.icon"></i>
        </div>

        <!--Name and text-->
        <div class="mr-1 w-75 tr-cookie-item">
          <p><b>{{ cookie.name }}</b></p>
          <div>
            <p class="m-0">{{ cookie.title }}</p>
            <div v-if="cookie.content">
              <div>
                <a v-b-toggle :href="'#co_content'+cookie.id" @click.prevent>
                  <span :id="'co_span'+cookie.id">Mehr anzeigen</span>
                </a>
              </div>
              <b-collapse
                  @shown="collapseShow(cookie.id)"
                  @hidden="collapseHide(cookie.id)"
                  :id="'co_content'+cookie.id"
              >
                <p class="mt-2" v-html="cookie.content"></p>
              </b-collapse>
            </div>
          </div>
        </div>

        <!--Checkbox btn-->
        <div  class="tr-cookie-wrapper-checkbox">
          <item :data="cookie" :index="index"></item>
        </div>
      </div>
    </section>

  </div>
</template>

<script>
import item from './Item'

export default {
  name: "Settings",
  components: {
    item
  },
  props: {
    data: null,
  },
  computed: {},
  methods: {
    collapseShow: function (id) {
      let refElem = 'co_span' + id;
      document.getElementById(refElem).innerHTML = 'Weniger anzeigen';
    },
    collapseHide: function (id) {
      let refElem = 'co_span' + id;
      document.getElementById(refElem).innerHTML = 'Mehr anzeigen';
    },

  },
}
</script>

<style lang="scss" scoped>
p {
  font-size: 1em;
}

.coo-w-70 {
  width: 70%;
}

.coo-w-5 {
  width: 5%;
}

.coo-w-3 {
  width: 3%;
}

.coo-w-20 {
  width: 20%;
}

.coo-w-10 {
  width: 10%;
}
</style>