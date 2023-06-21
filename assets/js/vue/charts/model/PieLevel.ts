/* eslint-disable no-unused-vars */

import Data from "./Data";
import Dataset from "./Dataset";
import DataElement from "./DataElement";
// @ts-ignore
import setting from "./../data/chartSettings.json";
import PieLevelApi from "../utility/PieLevelApi";

/**
 * Defines one level in the Navigation Pie.
 * Since the whole structure of the navigation Pie is based on a tree, each PieLevel Object works as a tree node.
 *
 * Developed into to a full Tree Node with methods for all included Nav Charts.
 */
export default class PieLevel {
  /** Used by NavigationChart only */
  public parentMinRadius: number;
  /** Used by NavigawtionChart only */
  public radiusMin: Array<number>;
  /** Used by NavigationChart only */
  public radiusMax: Array<number>;
  /** Used by Navigation Chart only
   * current Radius of this Level. */
  public radius: Array<string>;
  /** Used by all Charts */
  public name: string;
  /** @see getAverage */
  public value: number;
  /** Used by all Charts. */
  public color: string;
  /** Used by all Charts. */
  public catid: number;
  /** Used by all Charts. */
  public children: Array<PieLevel>;
  /** Used by all Charts. */
  public sourceData?: Dataset

  /**
   * @param name Displayed name. Is also used as ID by some components.
   * @param color
   * @param parentMinRadius Only needed for Navigation Chart. Radius from the center to the outside of the minimized ring of the parent.
   * @param children
   * @param sourceData Give this to most outer levels. Inside levels will compute this by themselves. You can change this behaviour by editing the constructor.
   */
  constructor(catid: number,name: string, color: string, parentMinRadius: number, children: Array<PieLevel>, sourceData?: Dataset) {
    this.parentMinRadius = parentMinRadius;
    this.name = name;
    this.color = color;
    this.catid = catid;
    if (this.parentMinRadius) {
      this.radiusMax = [this.parentMinRadius + setting.NavigationChart.gapToOutsideRingRadius, this.parentMinRadius + setting.NavigationChart.gapToOutsideRingRadius + setting.NavigationChart.outsideRingRadius];
      this.radiusMin = [this.parentMinRadius, this.parentMinRadius + setting.NavigationChart.insideRingRadius];
    } else { //PieLevel is PieRoot
      this.radiusMax = [0, setting.NavigationChart.centerElementRadius.max]
      this.radiusMin = [0, setting.NavigationChart.centerElementRadius.min]
    }
    this.radius = [this.radiusMax[0] + "%", this.radiusMax[1] + "%"];
    this.children = children;
    if (sourceData) this.sourceData = sourceData;
    else this.sourceData = new Dataset(this.getDataElements());
    if (this.sourceData.sourceData.length) this.value = this.getAverage();
    else this.value = 0;
  }

  /**
   * removes all keys from the {@link children} array except value and name. If useValueForAngle in {@link setting charSettings} is false, 100 is taken for all values.
   * @see getSeries
   * @returns an array of data objects built from value and name
   */
  public getData(): Array<Data> {
    if (setting.NavigationChart.useValueForAngle) return this.children.map(child => new Data(child.value, child.name));
    return this.children.map(child => new Data(100, child.name))
  }

  /**
   * removes all keys from the {@link children} array except color
   * @see getSeries
   * @returns an array of color strings corresponding to the children's colors
   */
  public getColor(): Array<string> {
    return this.children.map(child => child.color);
  }

  /**
   * Returns the series needed by an {@link https://echarts.apache.org/en/option.html#series-pie ECharts Pie}.
   * @param lowerSelected if inner ring, the subLevel selected on this level (is needed to take it's color and label). Null if this is the most outer level.
   * @returns the pieSeries of the pieLevel Object it is invoked on.
   */
  public getSeries(lowerSelected: PieLevel | null, group?: string): any {
    if (this.children.length > 0 && lowerSelected instanceof PieLevel) {
      //inner ring
      return {
        name: this.name,
        type: 'pie',
        foob:'bar',
        data: [new Data(lowerSelected.value, lowerSelected.name)],
        color: [lowerSelected.color],
        radius: [this.radiusMin[0] + "%", this.radiusMin[1] + "%"],
        hoverAnimation: false,
        selectedMode: false,
        itemStyle: {
          borderWidth: 1,
          borderColor: "#fff",
        },
        label: {
          position: "inside",
          show: false,
        }
      }
    }
    //outer ring
    return {
      name: this.name,
      fooba:'bar',
      hoverAnimation: false,
      itemStyle: {
        borderWidth: 1,
        borderColor: "#fff",
      },
      type: 'pie',
      data: this.getData(),
      color: this.getColor(),
      radius: [this.radiusMax[0] + "%", this.radiusMax[1] + "%"],
      selectedMode: false,
      label: {
        show: true,
        position: "outside",
        formatter: "  {b}  ",
        backgroundColor: "#eee",
        borderColor: "#aaa",
        borderWidth: 1,
        borderRadius: 4,
        rich: {
          a: {
            color: "#999",
            lineHeight: 22,
            align: "center",
          },
          hr: {
            borderColor: "#aaa",
            width: "100%",
            borderWidth: 0.5,
            height: 0,
          },
          b: {
            fontSize: 16,
            lineHeight: 33,
          },
          per: {
            color: "#eee",
            backgroundColor: "#334455",
            padding: [2, 4],
            borderRadius: 2,
          },
        },
      },
    }
  }

  /**
   * Maps all children of a {@link PieLevel} to a {@link https://echarts.apache.org/en/option.html#series-sunburst.data series-sunburst.data} Object.
   * @param pieLevel The PieLevel whose children are mapped.
   * @param remainingDepth depth of the children to be mapped. Recursion will stop when this param is casted to false. So don't use undefined.
   * @returns A data an Array of Objects of style: {name: string, value: number, children: Array<dataObject>/[]}
   */
  public mapSunburstSeries(pieLevel: PieLevel, remainingDepth: number): Array<any> {
    return pieLevel.children.map(child => {
      return {
        name: child.name,
        value: child.value ? child.value : 20,
        children: remainingDepth ? this.mapSunburstSeries(child, remainingDepth - 1) : [],
      }
    })
  }

  /**
   * Goes recursively through the children of all PieLevels, starting at selectedPie[0] and reading name, value and children.
   * @returns a {@link https://echarts.apache.org/en/option.html#series-sunburst SunBurst Series}
   */
  public getSunburstSeries(currentLevel: number, additionalDepth: number): any {
    return {
      type: "sunburst",
      data: this.mapSunburstSeries(this, additionalDepth + currentLevel),
      radius: ["0%", "100%"],
    }
  }

  /**
   * Computes the data for rose and pie (same return). Gradient computation happens here.
   * @param standardPieAngleValue Same as {@link GradientNavigation.vue.value}
   * @param width width of the whole chart container, used to compute the gradient
   * @param height height of the whole chart container, used to compute the gradient
   * @param greyCode outside color
   * @param group Only use data from one group.
   * @returns {@link https://echarts.apache.org/en/option.html#series-pie.data ECharts series-pie.data}
   */
  public getGradientChartData(standardPieAngleValue: number, width: number, height: number, greyCode: string, group?: string): Array<any> {
    return this.children.map(child => {

      return {
        value: standardPieAngleValue,
        name: child.name,
        catid:child.catid,
        itemStyle: {
          color: {
            type: "radial",
            x: '50%',
            y: '50%',
            r: Math.min(width, height) / 4, //min of width and height / 4 is the radius of the pie chart, because radius (~diameter) [0% - 50%] in options
            colorStops: [{
              offset: 0, color: child.color
            }, {
              offset: child.getAverage(group) / 100, color: child.color
            }, {
              offset: child.getAverage(group) / 100, color: greyCode
            }, {
              offset: 1, color: greyCode
            }, {
              offset: 1, color: child.color
            },],
            global: true,
          }
        }
      }
    })
  }

  /**
   * Gets all Data Elements from all children recursively.
   * If sourceData is already available in a PieLevel, the method will use it and avoid computing it from scratch.
   * @private
   */
  private getDataElements(): Array<DataElement> {
    if (this.sourceData) return this.sourceData.sourceData;
    const res = [] as Array<DataElement>;
    return res.concat(...this.children.map(child => child.getDataElements()));
  }

  /**
   * This is just a shortcut, since the normal function calls would be pretty long.
   * @see Dataset
   */
  public getAverage(group?: string): number {
    return Dataset.computeAverage(this.sourceData!.getAnswers(undefined, group));
  }


  /**
   * Returns options for the {@link @/components/Details.vue Details} Component.
   * If this has no children, a similar function in {@link @/src/model/Dataset Dataset} is called which returns Data sorted to single Question Tags instead of PieLevel.children names.
   * @see getAllOptions
   */
  public getAllDetails(): any {

    const res = {
      tooltip: {},
      radar: {
        name: {
          show: true,
          position: "outside",
          formatter: "  {value} ",
          backgroundColor: "#eee",
          fontSize:16,
          fontFamily:'regular',
          borderColor: "#aaa",
          borderWidth: 1,
          borderRadius: 4,
          padding: 5,
          rich: {
            a: {
              color: "#999",
              lineHeight: 22,
              align: "center",
            },
            hr: {
              borderColor: "#aaa",
              width: "100%",
              borderWidth: 0.5,
              height: 0,
            },
            b: {
              fontSize: 16,
              lineHeight: 33,
            },
            per: {
              color: "#eee",
              backgroundColor: "#334455",
              padding: [2, 4],
              borderRadius: 2,
            },
          },
        },
        indicator: this.children.map(child => {
          return {name: child.name, max: 100, color: child.color}
        }),

      },
      series: [
        {
          name: "Radar",
          type: "radar",
          data: [
            /*{
              name: "Durchschnitt2",
              value: this.children.map(child => Dataset.computeAverage(child.sourceData!.getAnswers())),
            }*/
          ],
        }
      ] as Array<any>,
    }
    const self = this;
    this.sourceData!.groupTags.forEach(group => {
      res.series[0].data.push({
        name: group,
        value: self.children.map(child => Dataset.computeAverage(child.sourceData!.getAnswers(undefined, group))),
      });
        });
    res.series.push(res.series.shift());
    return res;
  }


  public getBoxplotDetails(){

    const res = {
      tooltip: {},
      xAxis: {
        type: 'category',
        data: ['Boxplot-1'+Math.random(),  'Boxplot-2'+Math.random(),  'Boxplot-3'+Math.random()]
      },
      yAxis: {
        type: 'value'
      },
      series: [
        {
          name: 'boxplot',
          type: 'boxplot',
          tooltip: {formatter: this.formatter},
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
    return res;
  }

  public  formatter(param:any) {
    return [
      'Experiment ' + param.name + ': ',
      'upper: ' + param.data[0],
      'Q1: ' + param.data[1],
      'median: ' + param.data[2],
      'Q3: ' + param.data[3],
      'lower: ' + param.data[4]
    ].join('<br/>');
  }

}

