<template>
 <div> <v-chart :options="bar"/>
 </div>
</template>

<style>
/**
 * The default size is 600px×400px, for responsive charts
 * you may need to set percentage values as follows (also
 * don't forget to provide a size for the container).
 */
.echarts {
  width: 100%;
  height: 100%;
  min-width: 270px;
  min-height: 270px;
}
</style>

<script>

import ECharts from 'vue-echarts'

export default {
  name: 'charttest',
  props: {
    apiUrl: Object
  },
  components: {
    'v-chart': ECharts
  },
  data () {
    let data = []
    for (let i = 0; i <= 10; i++) {
      let t = i / 180 * Math.PI
      let r = Math.sin(2 * t) * Math.cos(2 * t)

      data.push([r, i])
    }

    return {
      bar: this.apiUrl
    };
  }
}
</script>
