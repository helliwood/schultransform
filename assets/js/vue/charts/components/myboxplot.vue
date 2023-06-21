<template>
  <v-chart :options="bar"/>

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
  min-height: 1470px;
}
</style>

<script>
import ECharts from 'vue-echarts'

export default {
  name: 'charttest',
  components: {
    'v-chart': ECharts
  },


  data () {
     return {
      bar: {
        xAxis: {
          type: 'category',
          data: ['Boxplot-1',  'Boxplot-2',  'Boxplot-3']
        },
        yAxis: {
          type: 'value'
        },
        series: [
          {
            name: 'boxplot',
            type: 'boasdxplot',
            data: [
              [713.75, 807.5, 810, 870, 963.75],
              [713.75, 807.5, 810, 870, 963.75],
              {
                value: [713.75, 807.5, 810, 870, 963.75],
                itemStyle: {
                  color: ' #0d0ded ',
                  borderColor: '#000000'
                }
              },
            ],
          }
        ]
      }
    };
  }
}
</script>
