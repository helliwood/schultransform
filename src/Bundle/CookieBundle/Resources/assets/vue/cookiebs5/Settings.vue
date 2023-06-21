<template>

  <div class="p-2" v-if="data">
    <!--Content-->
    <p class="lh-base fs-6" v-if="data.contentsub" v-html="data.contentsub"></p>

    <!--Cookies-->
    <section v-if="data.item" v-for="(cookie, index) in data.item"
             class="d-flex flex-column border-bottom mb-2 pb-2 px-1 px-sm-0"
    >
      <div class="d-flex flex-column flex-sm-row px-2" :class="cookie.highlight?'border border-primary py-2':''">
        <!--Icon-->
        <div class="coo-w-7 ms-0 ms-sm-2 me-lg-0 me-3">
          <i :class="cookie.icon"></i>
        </div>

        <!--Name and text-->
        <div class="me-1 w-75 ">
          <p class="lh-base fs-6"><b>{{ cookie.name }}</b></p>
          <div>
            <p class="m-0 lh-base fs-6">{{ cookie.title }}</p>
            <div v-if="cookie.content">
              <div>
                <a class="lh-base fs-6" v-b-toggle :href="'#co_content'+cookie.id" @click.prevent>
                  <span :id="'co_span'+cookie.id">Mehr anzeigen</span>
                </a>
              </div>
              <b-collapse
                  @shown="collapseShow(cookie.id)"
                  @hidden="collapseHide(cookie.id)"
                  :id="'co_content'+cookie.id"
              >
                <p class="mt-2 lh-base fs-6" v-html="cookie.content"></p>
              </b-collapse>
            </div>
          </div>
        </div>

        <!--Checkbox btn-->
        <div>
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
.coo-w-7 {
  width: 7%;
}
</style>