<template>
  <div style="height: 280px; overflow: hidden">
    <e-charts autoresize :init-options="initOptions" :options="options" @click="ClickHandler"></e-charts>
  </div>
</template>

<script>
import ECharts from "vue-echarts/components/ECharts.vue";

export default {
  name: "result-chart-gauge",
  components: {ECharts},
  props: {
    chartValue: Number | null,
    chartColor: String | null,
    tickColor: String | null,
    theUrl: String | null,
    showDetail: Boolean | null,
    chartHeight: {
      type: Number,
      default: 400
    }
  },
  data: function () {
    return {
      initOptions: {
        renderer: "svg",
        width: 'auto',
        height: this.chartHeight,
      },
      options: {
        series: [
          {
            type: 'gauge',
            startAngle: 180,
            endAngle: 0,
            min: 0,
            max: 7,
            splitNumber: 7,
            itemStyle: {
              color: this.chartColor,
              shadowColor: 'rgba(0,0,0,0.3)',
              shadowBlur: 10,
              shadowOffsetX: 2,
              shadowOffsetY: 2
            },
            progress: {
              show: true,
              roundCap: true,
              width: 10
            },
            pointer: {
              icon: 'path://M2090.36389,615.30999 L2090.36389,615.30999 C2091.48372,615.30999 2092.40383,616.194028 2092.44859,617.312956 L2096.90698,728.755929 C2097.05155,732.369577 2094.2393,735.416212 2090.62566,735.56078 C2090.53845,735.564269 2090.45117,735.566014 2090.36389,735.566014 L2090.36389,735.566014 C2086.74736,735.566014 2083.81557,732.63423 2083.81557,729.017692 C2083.81557,728.930412 2083.81732,728.84314 2083.82081,728.755929 L2088.2792,617.312956 C2088.32396,616.194028 2089.24407,615.30999 2090.36389,615.30999 Z',
              length: '75%',
              width: 12,
              offsetCenter: [0, '5%']
            },
            axisLine: {
              roundCap: true,
              lineStyle: {
                width: 10
              }
            },
            axisTick: {
              splitNumber: 2,
              lineStyle: {
                width: 1,
                color: this.tickColor != null ? this.tickColor : '#999'
              }
            },
            splitLine: {
              length: 12,
              lineStyle: {
                width: 2,
                color: this.tickColor != null ? this.tickColor : '#999'
              }
            },
            axisLabel: {
              distance: 20,
              color: this.tickColor != null ? this.tickColor : '#999',
              fontSize: 14
            },
            title: {
              show: false
            },
            detail: {
              show: this.showDetail != null ? this.showDetail : true,
              backgroundColor: '#fff',
              borderColor: '#b3b3b3',
              borderWidth: 1,
              width: '50%',
              lineHeight: 30,
              height: 28,
              borderRadius: 8,
              offsetCenter: [0, '35%'],
              valueAnimation: true,
              formatter: function (value) {
                return '{value|' + value.toFixed(2) + '}';
              },
              rich: {
                value: {
                  fontSize: 20,
                  fontWeight: 'normal',
                  color: '#777'
                },
                unit: {
                  fontSize: 20,
                  color: '#990000',
                  padding: [0, 0, -20, 10]
                }
              }
            },
            data: [
              {
                value: this.chartValue
              }
            ]
          }
        ]
      }
    }
  },
  watch: {
    chartValue: function (val) {
      this.options.series[0].data[0].value = val ? val : 0;
    },
    chartColor: function (val) {
      this.options.series[0].itemStyle.color = val;
    }
  },
  created() {
  },
  methods: {
    update: function () {
    },
    ClickHandler: function(event, args) {
      if(this.theUrl && this.theUrl!=''){
        console.log(this.theUrl);
        window.open(this.theUrl, '_blank').focus();
      }
    }
  }
}
</script>

<style lang="scss" scoped>

</style>