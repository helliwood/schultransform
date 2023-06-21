// eslint-disable-next-line no-unused-vars
import DataElement from "./../model/DataElement";
import PieLevel from "./../model/PieLevel";
// @ts-ignore
import Dataset from "./../model/Dataset";
// @ts-ignore
import settings from "./../data/chartSettings.json";

import axios from "../../../../../node_modules/axios";
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

interface ChartSettingsJSON {
  NavigationChart: {
    centerElementRadius: { max: number, min: number },
    gapToOutsideRingRadius: number,
    outsideRingRadius: number,
    insideRingRadius: number,
    useValueForAngle: boolean
  };
}

interface PieLevelJSON {
  catid: number,
  name: string;
  color?: string;
  children: Array<PieLevelJSON>;
  sourceData?: Array<DataElement>;
}

export default class PieLevelApi {
  /**
   * @param input Object from the JSON File
   * @param parentMinRadius
   * @param color
   * @private
   */

  public static params?: string;

  private static json = {
    catid: '',
    name: '',
    color: '',
    children: [],
    sourceData: [],
    boxdata: []
  }


  private static buildPie(input: PieLevelJSON, parentMinRadius: number, color: string): PieLevel {
    if (input.color) color = input.color;
    if (input.sourceData) return new PieLevel(input.catid,input.name, color, parentMinRadius, input.children.map(childJSON => PieLevelApi.buildPie(childJSON, parentMinRadius + settings.NavigationChart.insideRingRadius, color)), new Dataset(input.sourceData));
    return new PieLevel(input.catid,input.name, color, parentMinRadius, input.children.map(childJSON => PieLevelApi.buildPie(childJSON, parentMinRadius + settings.NavigationChart.insideRingRadius, color)));
  }



  /**
   * Creates the Pie Level tree Structure.
   * @returns rootPie
   */
  public static async getRootPie() {
    if(PieLevelApi.params) {
      await PieLevelApi.callApi();
      return new PieLevel(Number(PieLevelApi.json.catid),PieLevelApi.json.name, PieLevelApi.json.color, 0, PieLevelApi.json.children.map((childJSON: PieLevelJSON) => PieLevelApi.buildPie(childJSON, settings.NavigationChart.centerElementRadius.min, "red")));
    }
  }


  public static getJson(){
    return PieLevelApi.json;
  }

  private static callApi() {
    let promise = axios.get("/chart-results" + ((PieLevelApi.params)? '?'+PieLevelApi.params:'')  );
    return new Promise<void | any>( (resolve, reject) => {
        promise.then((data) => {
            // Here we could override the busy state, setting isBusy to false
            PieLevelApi.json = data.data;
            resolve(PieLevelApi.json)
        }).catch(error => {
            return [];
        });
    });
  }
}
