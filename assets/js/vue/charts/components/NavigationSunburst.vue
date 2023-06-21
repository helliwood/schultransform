<template>
  <div class="sunburst">
    <!--<h2>Sunburst as Navigation Chart</h2>
    <label>
      <input type="number" min="2" max="100" v-model="$store.state.visibleLevel">
    </label>//-->
      <chart
          flex
          :options="options"
          :init-options="$store.state.initOptions"
          @click="onClick"
          ref="sunburst"
      />
    </div>
</template>

<script lang="ts">

import {Component, Vue, Watch} from "vue-property-decorator";
import ECharts from "vue-echarts/components/ECharts.vue";
import PieLevel from "./../model/PieLevel";

interface SeriesData {
  name: string;
  value: number;
  children: Array<SeriesData>;
}

@Component({
  components: {
    chart: ECharts,
  }
})
export default class NavigationSunburst extends Vue {

  public options = {};

  private _pieRoot = null;

  mounted() {
    // @ts-ignore
    this.$refs.sunburst.showLoading();
    var self = this;
    this.$store.getters.selectedPie.then( (data:any) => {
      this._pieRoot = data;
      setTimeout(() => {
        var opt = { series: {}};
        opt.series = data[0]!.getSunburstSeries(0, parseInt(this.$store.state.visibleLevel));
        // @ts-ignore
        this.$refs.sunburst.hideLoading();

        self.options = opt;

        window.onresize = () => {
          // @ts-ignore
          self.$refs.sunburst.resize({silent:true});
        }
      },1000);
    });
  }

  /**
   * Navigates through the pie.
   * Resets every value of selectedPie. Resets Current Level. Resets displayedDetails.
   * names of {@link options} series' and PieLevel name must match.
   * @param event {@link https://echarts.apache.org/en/api.html#events.Mouse%20events.click EChats click event}
   */
  public onClick(event: any) {
    let i = 1;
    for (i; i < this.$store.state._selectedPie.length; i++) {
      if (i < event.treePathInfo.length) this.$store.state._selectedPie[i] = this.$store.state._selectedPie[i - 1]!.children.find((child: PieLevel) => child.name == event.treePathInfo[i].name);
      else this.$store.state._selectedPie[i] = null;
    }
    if (!event.data.children.length) {
      this.$store.state.currentLevel = event.treePathInfo.length - 2;
      this.$store.state.displayedDetails = this.selectedPie[this.currentLevel + 1]!.sourceData ? this.selectedPie[this.currentLevel + 1] : null;
    } else {
      this.$store.state.currentLevel = event.treePathInfo.length - 1;
      this.$store.state.displayedDetails = this.selectedPie[this.currentLevel]!.sourceData ? this.selectedPie[this.currentLevel] : null;
    }

  }

  get selectedPie() {
    return this.$store.state._selectedPie;
  }

  get visibleLevel(): number {
    return parseInt(this.$store.state.visibleLevel);
  }

  get currentLevel() {
    return this.$store.state.currentLevel;
  }

  get seriesDepth() {
    return this.currentLevel + this.visibleLevel;
  }

  /**
   * Goes recursively down in the PieLevel and series Data Tree to adjust to a new data depth.
   * Using this triggers rerendering of the chart and is not recommended.
   * @param seriesDataObject A {@link SeriesData SeriesData Interface} packed into a object to keep the Object references in the recursion stack. Must be on the same level as pieLevel.
   * @param pieLevel A {@link PieLevel PieLevel Object}, must be the same level as seriesDataObject.
   * @param remainingNewDepth If 0 recursion is stopped and the current child data is set to [].
   * @param remainingOldDepth If 0 recursion is stopped and the current child data is filled with data from pieLevel.
   * @deprecated
   * @private
   */
  private seriesDepthChanged(seriesDataObject: {seriesData: SeriesData}, pieLevel: PieLevel, remainingNewDepth: number, remainingOldDepth: number) {
    if (remainingOldDepth && remainingNewDepth) {
      for (let i = 0; i < seriesDataObject.seriesData.children.length; i++) {
        this.seriesDepthChanged({seriesData: seriesDataObject.seriesData.children[i]}, pieLevel.children[i], remainingNewDepth - 1, remainingOldDepth - 1)
      }
    } else {
      seriesDataObject.seriesData.children = remainingNewDepth > remainingOldDepth ? pieLevel.mapSunburstSeries(pieLevel, remainingNewDepth - 1) : [];
    }
  }

  /**
   * Watcher is expected to be executed
   * if the user changes the amount of levels he wants to get displayed
   * or if the current level changes
   * Adjusts sunburst by a recursive call of {@link seriesDepthChanged}
   * @deprecated Forces sunburst to reload. Not recommended for navigation.
   * @param newDepth
   * @param oldDepth
   * @see seriesDepthChanged
   * @see seriesDepth
   */
  @Watch('seriesDepth')
  public seriesDepthChangedHandler(newDepth: number, oldDepth: number) {
    // @ts-ignore
    if(!this.options.series) return;
    // if (newDepth > oldDepth) {
    //User goes deeper into Detail the sunburst gets bigger
    // @ts-ignore
    for (let i = 0; i < this.options.series.data.length; i++) {
      // @ts-ignore
      this.seriesDepthChanged({seriesData: this.options.series.data[i] as SeriesData}, this.selectedPie[0]?.children[i], newDepth, oldDepth);
    }
  }
}
</script>

<style scoped>
  .echarts {
    position: relative;
  }

  .sunburst {
    width: 300px;
    height: 300px;
  }
</style>
