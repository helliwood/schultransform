<template>
  <div style="width: 100%">
    <div v-if="dataIsLloaded" class="text-center">
      <div class="mx-1 badge badge-pill badge-primary" :class="'total' == groupToShow? 'badge-secondary' : 'badge-primary'" @click="toroot">
        Alle
      </div>
      <div class="mx-1 badge badge-pill badge-primary" :class="group == groupToShow? 'badge-secondary' : 'badge-primary'" v-for="group in pieRoot.sourceData.groupTags" :key="group" @click="groupToShow = group">
        {{group}}
      </div>
      <div v-if="haveSelection" class="mx-1 badge badge-pill badge-danger" @click="toroot">
        <i class="fad fa-backspace"></i> Zurücksetzen
      </div>
    </div>
    <chart
        flex
        :options="options"
        ref="pie"
        flex
        @click="onClick"
        @legendselectchanged="legendSelectHandler"
        :init-options="initOptions"
    />
    <slot v-if="dataClickedOn >= 0"></slot>
    <div class="detail_vue" v-if="dataClickedOn >= 0">
      <Details v-if="showDetails && pieToShow && pieToShow.sourceData.sourceData.length" :pie-to-show="pieToShow"/>
    </div>
  </div>
</template>

<script lang="ts">
/* eslint-disable @typescript-eslint/no-non-null-assertion */
/* eslint-disable @typescript-eslint/no-explicit-any */
/* eslint-disable @typescript-eslint/ban-ts-ignore */

import {Component, Prop, Vue, Watch} from 'vue-property-decorator'
import Details from "./Details.vue";
import ECharts from 'vue-echarts/components/ECharts.vue';
import  'echarts';
import PieLevel from './../model/PieLevel';
import PieLevelApi from "../utility/PieLevelApi";

const GREYCODE = "#e0e0e0";

@Component({
  components: {
    Details: Details,
    chart: ECharts
  }
})
export default class GradientNavigation extends Vue {

  public labelDisplayHtml = "on";

  @Watch("labelDisplayHtml")
  public labelDisplayHandler(newValue: String) {
    this.options.series[0].label.show = newValue == "on";
    this.options.series[0].labelLine.show = newValue == "on";
  }

  public legendDisplayHtml = "off";

  @Watch("legendDisplayHtml")
  public legendDisplayHandler(newValue: String) {
    this.options.legend.show = newValue == "on";
    this.options.legend.show = newValue == "on";
  }

  private haveSelection = false;
  private dataIsLloaded = false;
  private currentData = [];
  private _startData = null;
  private _pieRoot = null;

  public pieToShow = this._pieRoot;

  public groupToShow = "total";

  public chartType = "rose";

  /**
   * Index of the selected top level. If nothing is selected -1.
   */
  public dataClickedOn = -1;

  @Prop({default: 400})
  height!: number;

  @Prop({default: 400})
  width!: number;

  @Prop({default: 'chart=rose'})
  params!: string;

  @Prop({default: false})
  showDetails!: boolean;

  /**
   * Values the Chart uses. Rose increases this by 20% for displaying sublevels.
   * Just consider that the Sublevel Elements use a value of value / pieRoot.children[sublevel].children.length so value should be big enough that it can be devided without losing to much information.
   */
  @Prop({default: 1024})
  value!: number;

  public initOptions = {
    renderer: "svg",
    width: 'auto',
    height: this.height,
  }

  public options = this.getOptions();

  mounted() {
    // @ts-ignore
    this.$refs.pie.showLoading();
    var self = this;
    PieLevelApi.params = this.params;
    this.$store.getters.selectedPie.then( (data:any) => {
      this._pieRoot = data;
      setTimeout(() => {
        var opt = self.getOptions();
        opt.series[0].data = data[0]?.getGradientChartData(this.value, this.width, this.height, GREYCODE, this.groupToShow != "total" ? this.groupToShow : undefined);
        this._startData = opt.series[0].data;
        this.currentData = opt.series[0].data;
        // @ts-ignore
        this.$refs.pie.hideLoading();
        self.options = opt;
        this.dataIsLloaded = true;
        //console.log('set new data');
        //console.log(self.options);

        window.onresize = () => {
          // @ts-ignore
          self.$refs.pie.resize({silent:true});
          self.rerender();
        }
      },1000);
    });
    //console.log(this.options);
  }

  /**
   * Gets the options for a rose or pie. Which one to create is decided by inline conditions since the differences are marginal.
   * Notice that this is a normal method, no computed property to avoid rerendering of the chart all the time.
   * {@link https://echarts.apache.org/en/option.html#series-pie.data data} is calculated by {@link PieLevel.getGradientChartData}
   * @returns {@link https://echarts.apache.org/en/option.html ECharts Options}, containing one {@link https://echarts.apache.org/en/option.html#series-pie pie-series}
   */
  public getOptions(): any {
    return {
      legend: {
        show: false,
        orient: "horizontal",
        bottom: "0%",
        selectedMode: "false",
        selected: {},
      },
      series: [
        {
          name: "TNavChart",
          type: "pie",
          roseType: this.chartType == "rose" ? "area" : undefined,
          radius: ["0%", "50%"],
          selectedMode: false,
          label: {
            show: true,
            textStyle: {
              fontSize: '16',
            },
          },
          labelLine: {
            show: true,
          },
          emphasis: {
            itemStyle: {
              shadowBlur: 10,
              shadowOffsetX: 0,
              shadowColor: 'rgba(0, 0, 0, 0.5)'
            },
          },
          itemStyle: {
            borderWidth: 1,
            borderColor: "#fff"
          },
          // @ts-ignore
          data: (this._pieRoot)? this._pieRoot[0]?.getGradientChartData(this.value, this.width, this.height, GREYCODE, this.groupToShow != "total" ? this.groupToShow : undefined) : [],
        }
      ]
    }
  }

  /**
   * Watcher for the chart type selection. Forces reset if the chart type changes.
   * @see getOptions
   */
  @Watch("chartType")
  public chartTypeChangedHandler() {
    this.rerender();
  }

  @Watch("groupToShow")
  public groupToShowChangedHandler() {
    if(this.groupToShow != "total") {
      this.haveSelection = true;
    }
    this.rerender();
  }

  public toroot() {
    this.groupToShow = "total";
    this.haveSelection = false;
    /*this.currentData = this._startData;
    this.dataClickedOn = -1;
    var opt = this.getOptions();
    opt.series[0].data = this._pieRoot[0]?.getGradientChartData(this.value, this.width, this.height, GREYCODE, this.groupToShow != "total" ? this.groupToShow : undefined);
    this.options = opt;*/
    this.rerender();
  }

  public rerender() {
    var opt = this.getOptions();
    this.options = opt;
    this.dataClickedOn = -1;
    this.labelDisplayHandler(this.labelDisplayHtml);
    this.legendDisplayHandler(this.legendDisplayHtml);
  }

  public get pieRoot(): PieLevel {
    PieLevelApi.params = this.params;
    // @ts-ignore
    return (this._pieRoot!=null) ? this._pieRoot[0] : this.$store.getters.selectedPie[0];
  }

  public debug() {
    //console.log(this.$refs.pie);
  }

  /**
   * Goes deeper into detail.
   * This method covers the reaction for a pie as well as a rose, this is done by two inline conditions.
   * The main difference is the gradient radius as well as the normal date value, which have to be adopted to get the defining radius.
   * @param event {@link https://echarts.apache.org/en/api.html#events.Mouse%20events.click EChats click event}
   */
  public onClick(event: any) {
    console.log('current Category:' + event.data.catid);
    this.haveSelection = true;
    // @ts-ignore
    let foo = this.$root.$el.querySelector('.chart-bottom-text');
    if(foo){foo.removeAttribute('data-d-none')};
    if (this.dataClickedOn >= 0 && this.detailedIndices.includes(event.dataIndex)) {
      //this.subtopicClickHandler(event);
      return;
    } else {
      //console.assert(!!this.pieRoot.children[event.dataIndex].children.length, "The program tries to access children of an Element without children. Check if all level 0 elements have children. Behaviour undefined.")
      if (this.dataClickedOn >= 0) event.dataIndex = this.undoLastSelection(event);
      const clickedPieLevelChildren = this.pieRoot.children[event.dataIndex].children;
      this.options.series[0].data.splice(
          event.dataIndex,
          1,
          ...clickedPieLevelChildren
              .map((child: PieLevel) => {
                    return {
                      selected: this.chartType != "rose",
                      value: this.chartType != "rose" ? this.value / clickedPieLevelChildren.length : this.value * 1.2,
                      name: child.name,
                      itemStyle: {
                        color: {
                          type: "radial",
                          x: '50%',
                          y: '50%',
                          r: Math.min(this.width, this.height) / 4,
                          colorStops: [{
                            offset: 0, color: child.color
                          }, {
                            offset: child.getAverage(this.groupToShow != "total" ? this.groupToShow : undefined) / 100,
                            color: child.color
                          }, {
                            offset: child.getAverage(this.groupToShow != "total" ? this.groupToShow : undefined) / 100,
                            color: GREYCODE
                          }, {
                            offset: 1, color: GREYCODE
                          }, {
                            offset: 1, color: child.color
                          },],
                          global: true //Use Global Coords, meaning the radial gradient is placed around the global center of the pie, because x and y are the global coordinates
                        }
                      }
                    }
                  }
              )
      );
      this.dataClickedOn = event.dataIndex;
      // @ts-ignore
      this.pieToShow = this.pieRoot.children[event.dataIndex];
      if (this.chartType == "rose") this.options.series[0].data.forEach((element: any, index: number) => {
        if (!this.detailedIndices.includes(index)) element.itemStyle.color.r = (Math.min(this.width, this.height) / 4) / 1.2
      });
    }
  }

  /**
   * If the user clicks on a sublevel element, this function is called by {@link onClick}.
   * select of the current element is toggled. All other pie elements (including top Level) are unselected.
   * @param event {@link https://echarts.apache.org/en/api.html#events.Mouse%20events.click EChats click event}
   * @private
   */
  private subtopicClickHandler(event: any) {
    this.options.series[0].data[event.dataIndex].selected = !this.options.series[0].data[event.dataIndex].selected
    this.options.series[0].data.forEach((element: any, index: number) => {
      if (index != event.dataIndex) element.selected = false;
    })
    const index = this.options.series[0].data.find((element: any) => element.selected);
    if (index != undefined) {
      //Something was selected
      // @ts-ignore
      this.pieToShow = this.pieRoot.children[this.dataClickedOn].children[event.dataIndex - this.dataClickedOn];
    }
    // @ts-ignore
    else this.pieToShow = this.pieRoot.children[this.dataClickedOn];
  }

  /**
   * Restores the last selected top level to its original form.
   * Does only use global variables.
   */
  public undoLastSelection(event: any): number {
    this.options.series[0].data?.splice(
        this.dataClickedOn,
        this.detailedIndices.length,
        this.pieRoot.getGradientChartData(
            this.value,
            this.width,
            this.height,
            GREYCODE
        )[this.dataClickedOn]
    );
    if (event.dataIndex > this.dataClickedOn) event.dataIndex -= this.detailedIndices.length - 1;
    this.dataClickedOn = -1;
    return event.dataIndex;
  }

  /**
   * @returns All Indices which are part of the currently selected top Level. If none is selected an empty Array is returned. Indices correspond to the {@link PieLevel.children children} indices.
   */
  public get detailedIndices() {
    if (this.dataClickedOn < 0) return [];
    const res = [] as Array<number>
    if (this.pieRoot.children[this.dataClickedOn].children.length) this.pieRoot.children[this.dataClickedOn].children.forEach((_elem, index) => res.push(index + this.dataClickedOn));
    else res.push(this.dataClickedOn)
    return res;
  }

  /**
   * Prevents standard echarts LegendClick event (hiding selected) and executes {@link onClick}
   * @param event
   * @see https://echarts.apache.org/en/api.html#events.legendselectchanged LegendSelectChanged Event
   */
  public legendSelectHandler(event: any) {
    this.options.animation = false;
    const selected = event.name; // @ts-ignore
    this.$refs.pie.dispatchAction({
      type: 'legendSelect',
      name: selected,
    })
    this.options.animation = true;
    this.onClick({dataIndex: this.options.series[0].data.findIndex((dataElement :any) => dataElement.name == selected)});
  }
}
</script>

<style scoped>
  .echarts {
    width: 100%;
    height: inherit;
    overflow: hidden;
  }

  svg {
    width: 100% !important;
  }

  .detail_vue {
    width: 100%;
  }

  .badge {
    cursor: pointer;
    transition: all 200ms ease-in-out;
  }
  .badge:hover {
    transform: scale(1.2);
    transition: all 200ms ease-in-out;
  }

</style>
