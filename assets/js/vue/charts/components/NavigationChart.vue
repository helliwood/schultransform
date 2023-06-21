<template>
  <div>
    <chart
        :init-options="initOptions"
        :options="options"
        ref="pie"
        @click="onClick"
        @pieSelectChanged="pieSelectChanged"
        class="all"
    />
    <Details :pie-to-show="pieToShow" class="all"/>
  </div>
</template>

<style>
.all {
  width: 100%;
  height: auto;
}
</style>

<script lang="ts">
/* eslint-disable @typescript-eslint/no-non-null-assertion */
/* eslint-disable @typescript-eslint/no-explicit-any */
/* eslint-disable @typescript-eslint/ban-ts-ignore */
/* eslint-disable no-unused-vars */

import {Component, Prop, Vue} from "vue-property-decorator";
import PieLevel from "./../model/PieLevel";
import ECharts from "vue-echarts/components/ECharts.vue";
import "echarts";
import Details from "./Details.vue";
import PieLevelApi from "../utility/PieLevelApi";

@Component({
  components: {
    Details: Details,
    chart: ECharts,
  },
})
export default class NavigationChart extends Vue {

  @Prop({default: 'chart=rose'})
  params!: string;

  private _pieRoot = null;

  public get pieRoot(): PieLevel {
    PieLevelApi.params = this.params;
    // @ts-ignore
    this._pieRoot =  (this._pieRoot!=null) ? this._pieRoot[0] : this.$store.getters.selectedPie[0];
    // @ts-ignore
    return this._pieRoot;
  }

  public initOptions = {
    renderer: "canvas",
    width: 'auto',
    height: 600,
  }

  public pieToShow = this._pieRoot

  public currentLevel = 0;

  public selectedPieLevels = [null, null, null, null, null, null]

  /*private options = {
    series: [
      this.selectedPieLevels[0]!.getSeries(
          this.selectedPieLevels[1]
      ),
    ],
  };*/
  private options = {};

  mounted() {
    // @ts-ignore
    this.$refs.pie.showLoading();
    var self = this;
    PieLevelApi.params = this.params;
    this.$store.getters.selectedPie.then( (data:any) => {
      this._pieRoot = data[0];
      // @ts-ignore
      this.pieToShow = this._pieRoot;
      this.selectedPieLevels = [this._pieRoot, null, null, null, null, null];
      setTimeout(() => {
        var opt = { series: []};
        opt.series = [
          // @ts-ignore
          this.selectedPieLevels[0]!.getSeries(
              this.selectedPieLevels[1]
          ),
        ];
        // @ts-ignore
        this.$refs.pie.hideLoading();
        self.options = opt;
        window.addEventListener("resize", () => {
          // @ts-ignore
          self.$refs.pie.resize({silent:true});
        });
      },1000);
    });
  }

  /**
   * Event handler for a pie click, Decides if the User wants to go one level down/up or is selecting one of the outside rings.
   * If the click is located at the most outer level, all elements except the clicked one are unselected and the clicked select is toggled.
   * @event pieSelectChanged is fired by echarts when the select state changes. This invokes  {@link pieSelectChanged}.
   * @param event {@link https://echarts.apache.org/en/api.html#events.Mouse%20events.click EChats click event}
   */
  public onClick(event: any): void {
    const oldLevel = this.currentLevel;
    if (event.seriesIndex >= this.currentLevel) {
      // checks if the User wants to go deeper into the navigation
      if (
          // @ts-ignore
          this.selectedPieLevels[event.seriesIndex]!.children[
              event.dataIndex
              ].children.length == 0
      ) {
        // checks if the most outer level has been reached
        // @ts-ignore
        this.options.series[event.seriesIndex].data.forEach(
            (element: any, index: number) => {
              if (index != event.dataIndex)
                  // @ts-ignore
                this.$refs.pie.dispatchAction({
                  type: "pieUnSelect",
                  seriesIndex: event.seriesIndex,
                  dataIndex: index,
                });
            }
        );
        // @ts-ignore
        this.$refs.pie.dispatchAction({
          type: "pieToggleSelect",
          seriesIndex: event.seriesIndex,
          dataIndex: event.dataIndex,
        });
        return;
      }
      this.navToHigherLevel(oldLevel, ++this.currentLevel, event);
    } else {
      this.navToSubLevel(oldLevel, --this.currentLevel);
    }
    this.pieToShow = this.selectedPieLevels[
        this.currentLevel
        ]!;
    this.changeRouterPath();
  }

  /**
   * Navigates the pie to a higher level, does not check if the most outer ring has been reached. Has to be done before.
   * The selected pie array is refilled from 0 to newLevel
   * @see onClick
   * @param oldLevel old currentLevel, = newLevel - 1
   * @param newLevel should already be the same as currentLevel
   * @param event {@link https://echarts.apache.org/en/api.html#events.Mouse%20events.click EChats click event}
   */
  public navToHigherLevel(oldLevel: number, newLevel: number, event: any) {

    this.selectedPieLevels[newLevel] = this.selectedPieLevels[
        oldLevel
        // @ts-ignore
        ]!.children[event.dataIndex];
    for (let i = 0; i < this.currentLevel; i++) {
      // @ts-ignore
      this.options.series[i] = this.selectedPieLevels[i]!.getSeries(
          this.selectedPieLevels[i + 1]
      );
    }
    // @ts-ignore
    if (this.currentLevel < this.options.series.length) {
      // @ts-ignore
      this.options.series.splice(newLevel);
    }
    // @ts-ignore
    this.options.series.push(
        // @ts-ignore
        this.selectedPieLevels[this.currentLevel]!.getSeries(
            this.selectedPieLevels[this.currentLevel + 1]
        )
    );
  }

  /**
   * Navigates the pie one sub level down, does not check if the most outer ring has been reached. Has to be done before.
   * Refitting this method to cover more than a one level jump is possible.
   * The selected pie array is refilled from 0 to newLevel.
   * @param oldLevel
   * @param newLevel
   */
  public navToSubLevel(oldLevel: number, newLevel: number) {
    // @ts-ignore
    this.selectedPieLevels = this.selectedPieLevels.map(
        (element: PieLevel | null, index: number) =>
            index > newLevel ? null : element
    );
    // @ts-ignore
    this.options.series[oldLevel].data = [];
    // @ts-ignore
    this.options.series[newLevel] = this.selectedPieLevels[
        newLevel
        // @ts-ignore
        ]!.getSeries(this.selectedPieLevels[newLevel + 1]);
    // @ts-ignore
    this.options.series.splice(oldLevel + 1);
    // @ts-ignore
  }

  /**
   * Updates the router path, taking selectedPie[0 to currentLevel] as reference. disabled.
   * @param selected is added at the end. Use it if something is missing in selectedPie
   */
  public changeRouterPath(selected?: string): void {
    let path = "/sub/";
    this.selectedPieLevels.forEach(
        (element: PieLevel | null, index: number) => {
          if (element != null && index <= this.currentLevel)
            path += "," + element.name;
        }
    );
    if (selected) path += "," + selected;
    //Just visual. The Pie can't load from a path!: this.$router.push(path);
  }

  /**
   * Checks which part of the pie has been selected, by filtering the event.
   * If a new element is selected it is added to selectedPie at selectedPie[currentLevel + 1].
   * If a element is unselected and no new selected, selectedPie[currentLevel + 1] is set to null.
   * Calls {@link changeRouterPath} and adds the selected element
   * @param event {@link https://echarts.apache.org/en/api.html#events.pieselectchanged ECarts Event}
   */
  public pieSelectChanged(event: any): void {
    const selectedName: string = Object.keys(event.selected).filter(
        (key) => event.selected[key]
    )[0];
    this.changeRouterPath(selectedName);
    if (selectedName) {
      this.selectedPieLevels[
      this.currentLevel + 1
          ] = this.selectedPieLevels[
          this.currentLevel
          // @ts-ignore
          ]!.children.filter((element: PieLevel) => element.name == selectedName)[0];
      this.pieToShow = this.selectedPieLevels[
      this.currentLevel + 1
          ]!;
    } else {
      this.selectedPieLevels[this.currentLevel + 1] = null;
      this.pieToShow = this.selectedPieLevels[
          this.currentLevel
          ]!;
    }
  }

  public stringify(): void {
    console.log(JSON.stringify(this.$store.state.selectedPie[0]))
  }
}
</script>
