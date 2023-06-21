<template>
  <div>
    <chart
        :options="options"
        :init-options="$store.state.optionsDetails"
        ref="pie"
        class="pie"
    />
    <!--<chart
        :options="options2"
        ref="pie"
        class="pie"
    />-->
    </div>
</template>

<script lang="ts">
/* eslint-disable @typescript-eslint/no-non-null-assertion */
/* eslint-disable @typescript-eslint/no-explicit-any */
/* eslint-disable @typescript-eslint/ban-ts-ignore */
/* eslint-disable no-unused-vars */

import ECharts from "vue-echarts/components/ECharts.vue";
import {Component, Vue, Prop, Watch} from "vue-property-decorator";
import "echarts";
import PieLevel from "./../model/PieLevel";

@Component({
  components: {
    chart: ECharts,
  },
})
export default class Details extends Vue {

  public initOptions = {
    renderer: "svg",
    width: 'auto',
    height: 800,
  }

  @Prop() pieToShow!: PieLevel;

  public options = {};
  public options2 ={};

  mounted() {
    var self = this;
    if(this.pieToShow) {
      this.options = this.pieToShow.getAllDetails();
      this.options2 = this.pieToShow.getBoxplotDetails();
    }

    window.addEventListener("resize", () => {
         });
  }

  get sourceData () {
    return this.pieToShow.sourceData;
  }

  public highlight(): void { // @ts-ignore
    this.$refs.radar.dispatchAction({ type: "highlight", dataIndex: 1 });
  }

  @Watch("pieToShow")
  pieToShowAdjust (newPieToShow: PieLevel) {

    //@ts-ignore
    this.options = newPieToShow.getAllDetails();
    this.options2 = this.pieToShow.getBoxplotDetails();
  }
}
</script>
<style scoped>

.radar {
  width: 100%;
  height: auto;
}
</style>
