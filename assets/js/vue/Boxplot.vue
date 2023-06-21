<template>
  <div class="mt-3">
    <h2 class="text-center">{{ category.name }}</h2>
    <div ref="container">
      <div ref="chart"></div>
    </div>
  </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from 'vue-property-decorator'
import Konva from "konva";

interface Category {
  name: string,
  questionnaires: Questionnaire[]
};

interface Questionnaire {
  name: string,
  values: number[]
};

interface BoxplotData {
  name: string,
  values: number,
  middleIdx: number,
  min: number,
  q1: number,
  median: number,
  q3: number,
  max: number,
  iqr: number,
  lowerOutliers: number[],
  higherOutliers: number[]
};

@Component({
  name: "boxplot"
})
export default class Boxplot extends Vue {
  @Prop() readonly category: Category;

  private boxplots: BoxplotData[] = [];
  private stage: Konva.Stage;
  private sceneWidth: number = 800;
  private sceneHeight: number = 400;
  private margin: number = 50;

  mounted() {
    this.stage = new Konva.Stage({
      container: this.$refs.chart as HTMLDivElement,
      width: this.sceneWidth,
      height: this.sceneHeight
    });

    this.fitStageIntoParentContainer();
    // adapt the stage on any window resize
    window.addEventListener('resize', this.fitStageIntoParentContainer);

    this.category.questionnaires.forEach((q: Questionnaire) => {
      this.createBoxplot(q);
    });
    this.redraw();
  }

  redraw() {
    this.stage.removeChildren();
    this.stage.clear();
    this.drawLegend();
    this.drawBoxplots();
    this.drawTooltip();
  }

  drawTooltip() {
    let tooltipLayer = new Konva.Layer();
    let tooltip = new Konva.Group({ name: "tooltip", visible: false });
    let rect = new Konva.Rect({
      x: 0,
      y: 0,
      width: 200,
      height: 200,
      stroke: "#6881DD",
      strokeWidth: 1,
      shadowColor: 'black',
      shadowBlur: 10,
      shadowOffset: { x: 2, y: 2 },
      shadowOpacity: 0.2,
      fill: "rgba(255,255,255,1)"
    });
    tooltip.add(rect);
    let title = new Konva.Text({
      name: "tooltipTitle",
      text: "Blabla",
      x: 10,
      y: 10,
      fill: "#666",
      fontSize: 14,
      fontVariant: "bold",
      width: 180,
      height: 35
    });
    tooltip.add(title);
    let text = new Konva.Text({
      name: "tooltipText",
      text: "Blabla",
      x: 10,
      y: 40,
      fill: "#666",
      fontSize: 12,
      lineHeight: 1.6,
      width: 180,
      height: 160
    });
    tooltip.add(text);
    tooltipLayer.add(tooltip);
    tooltip.setPosition({ x: 200, y: 200 });
    this.stage.add(tooltipLayer);
  }

  drawBoxplots() {
    let layer = new Konva.Layer();
    let scale = 0.25;
    let segmentWidth = (this.sceneWidth - this.margin * 2) / this.boxplots.length;
    let segmentHeight = ((this.sceneHeight - (this.margin * 2)) / 7);
    this.boxplots.forEach((b: BoxplotData, i: number) => {
      let boxplotGroup = new Konva.Group({ name: b.name });
      let x = this.margin + (segmentWidth) * i;
      let boxStartX = x + (segmentWidth * (1 - scale)) / 2;
      let boxEndX = boxStartX + segmentWidth * scale;
      let boxMiddleX = boxStartX + (segmentWidth * scale / 2);
      let boxEndY = this.sceneHeight - (segmentHeight * b.q3 + this.margin);
      let boxStartY = this.sceneHeight - (segmentHeight * b.q1 + this.margin);
      let rect = new Konva.Rect({
        x: boxStartX,
        y: boxEndY,
        width: segmentWidth * scale,
        height: boxStartY - boxEndY,
        stroke: "#6881DD",
        strokeWidth: 2,
        fill: "rgba(255,255,255,0.75)"
      });
      boxplotGroup.add(rect);

      let medianY = this.sceneHeight - (segmentHeight * b.median + this.margin);
      let median = new Konva.Line({
        width: 1,
        points: [boxStartX, medianY, boxEndX, medianY],
        stroke: "#6881DD",
        strokeWidth: 2
      });
      boxplotGroup.add(median);

      let whiskerMaxY = this.sceneHeight - (segmentHeight * b.max + this.margin);
      let whiskerMax = new Konva.Line({
        width: 1,
        points: [boxStartX, whiskerMaxY, boxEndX, whiskerMaxY],
        stroke: "#6881DD",
        strokeWidth: 2
      });
      boxplotGroup.add(whiskerMax);

      let whiskerMaxLine = new Konva.Line({
        width: 1,
        points: [boxMiddleX, whiskerMaxY, boxMiddleX, boxEndY],
        stroke: "#6881DD",
        strokeWidth: 2
      });
      boxplotGroup.add(whiskerMaxLine);

      let whiskerMinY = this.sceneHeight - (segmentHeight * b.min + this.margin);
      let whiskerMin = new Konva.Line({
        width: 1,
        points: [boxStartX, whiskerMinY, boxEndX, whiskerMinY],
        stroke: "#6881DD",
        strokeWidth: 2
      });
      boxplotGroup.add(whiskerMin);


      let whiskerMinLine = new Konva.Line({
        width: 1,
        points: [boxMiddleX, whiskerMinY, boxMiddleX, boxStartY],
        stroke: "#6881DD",
        strokeWidth: 2
      });
      boxplotGroup.add(whiskerMinLine);

      b.lowerOutliers.forEach((v: number) => {
        let circle = new Konva.Circle({
          x: boxMiddleX,
          y: this.sceneHeight - (segmentHeight * v + this.margin),
          radius: segmentWidth * scale / 10,
          fill: '#A7D691',
        });
        boxplotGroup.add(circle);
      });

      b.higherOutliers.forEach((v: number) => {
        let circle = new Konva.Circle({
          x: boxMiddleX,
          y: this.sceneHeight - (segmentHeight * v + this.margin),
          radius: segmentWidth * scale / 10,
          fill: '#A7D691',
        });
        boxplotGroup.add(circle);
      });
      layer.add(boxplotGroup);

      boxplotGroup.on("mousemove", () => this.onMouseOver(b, boxplotGroup));
      boxplotGroup.on("mouseleave", () => this.onMouseLeave(b, boxplotGroup));

      // Labels
      let label = new Konva.Text({
        text: b.name,
        x: x,
        y: this.sceneHeight - this.margin + 15,
        fill: "#666",
        fontSize: 12,
        align: "center",
        width: segmentWidth
      });
      layer.add(label);
    });
    this.stage.add(layer);
  }

  onMouseOver(boxplotData: BoxplotData, boxplotGroup: Konva.Group) {
    let tooltip = this.stage.findOne('.tooltip');
    let pos = this.stage.getPointerPosition() as Konva.Vector2d;
    if (pos.x > this.sceneWidth - 200) {
      pos.x = pos.x - 200 - 10;
    } else {
      pos.x += 10;
    }
    if (pos.y > this.sceneHeight - 200) {
      pos.y = pos.y - 200;
    }
    tooltip.setPosition(pos);
    let tooltipTitle = this.stage.findOne('.tooltipTitle') as Konva.Text;
    tooltipTitle.text(boxplotData.name);
    let tooltipText = this.stage.findOne('.tooltipText') as Konva.Text;
    tooltipText.text(
        "Fragebögen: " + boxplotData.values + "\n" +
        "Max: " + boxplotData.max.toFixed(2) + "\n" +
        "Q3: " + boxplotData.q3.toFixed(2) + "\n" +
        "Median: " + boxplotData.median.toFixed(2) + "\n" +
        "Q1: " + boxplotData.q1.toFixed(2) + "\n" +
        "Min: " + boxplotData.min.toFixed(2) + "\n" +
        (boxplotData.lowerOutliers.length > 0 || boxplotData.higherOutliers.length > 0 ? "Ausreißer: " : "") +
        (boxplotData.lowerOutliers.length > 0 ? boxplotData.lowerOutliers.filter((v, i, a) => a.indexOf(v) === i).map(a => a.toFixed(2)).join(", ") : "") +
        (boxplotData.lowerOutliers.length > 0 && boxplotData.higherOutliers.length > 0 ? ", " : "") +
        (boxplotData.higherOutliers.length > 0 ? boxplotData.higherOutliers.filter((v, i, a) => a.indexOf(v) === i).map(a => a.toFixed(2)).join(", ") : "")
    );
    tooltip.visible(true);

    (boxplotGroup.getChildren() as Konva.Shape[]).forEach((c: Konva.Shape) => {
      c.shadowEnabled(true);
      c.shadowBlur(10);
      c.shadowOffset({ x: 0, y: 0 });
      c.shadowOpacity(0.2);
    });
  }

  onMouseLeave(boxplotData: BoxplotData, boxplotGroup: Konva.Group) {
    let tooltip = this.stage.findOne('.tooltip');
    tooltip.visible(false);

    (boxplotGroup.getChildren() as Konva.Shape[]).forEach((c: Konva.Shape) => {
      c.shadowEnabled(false);
    });
  }

  drawLegend() {
    let layer = new Konva.Layer();
    let lineHeight: number = ((this.sceneHeight - (this.margin * 2)) / 7);
    for (let i = 7; i >= 0; i--) {
      let value = new Konva.Text({
        text: String(i),
        x: this.margin - 15,
        y: this.margin + lineHeight * (7 - i) - 4,
        fill: "#666"
      });
      layer.add(value);

      if (i % 2 === 0) {
        let rect = new Konva.Rect({
          x: this.margin,
          y: this.margin + lineHeight * (6 - i),
          width: this.sceneWidth - this.margin * 2,
          height: lineHeight,
          fill: "#F3F5F8"
        });
        layer.add(rect);
      }
      let line = new Konva.Line({
        width: 1,
        points: [this.margin, this.margin + lineHeight * i, this.sceneWidth - this.margin, this.margin + lineHeight * i],
        stroke: "#AAA",
        strokeWidth: 0.5
      });
      layer.add(line);
    }
    this.stage.add(layer);
  }

  createBoxplot(questionnaire: Questionnaire) {
    // Hilfreich: https://www.statistikpsychologie.de/boxplot/
    let result: BoxplotData = {
      iqr: 0,
      lowerOutliers: [],
      higherOutliers: [],
      max: 0,
      median: 0,
      middleIdx: 0,
      min: 0,
      name: questionnaire.name,
      q1: 0,
      q3: 0,
      values: 0
    };
    let middle_idx = 0;
    if (questionnaire.values.length > 0) {
      result.values = questionnaire.values.length;
      result.min = questionnaire.values[0];
      result.max = questionnaire.values[questionnaire.values.length - 1];
      result.middleIdx = Math.floor((questionnaire.values.length - 1) / 2);
      result.median = questionnaire.values[result.middleIdx];

      let lowerValues: number[] = [];
      let higherValues: number[] = [];
      if (questionnaire.values.length % 2 === 0) {
        result.median = (result.median + questionnaire.values[result.middleIdx + 1]) / 2;
        questionnaire.values.forEach((n, i) => {
          if (i <= result.middleIdx) {
            lowerValues.push(n);
          } else {
            higherValues.push(n);
          }
        });
      } else {
        questionnaire.values.forEach((n, i) => {
          if (i < result.middleIdx) {
            lowerValues.push(n);
          } else if (i === result.middleIdx) { // Median zu lower and higher dazunehmen (kann man auch weg lassen)
            lowerValues.push(n);
            higherValues.push(n);
          } else if (i > result.middleIdx) {
            higherValues.push(n);
          }
        });
      }
      let lowerIdx = Math.floor((lowerValues.length - 1) / 2);
      result.q1 = lowerValues[lowerIdx];
      if (lowerValues.length % 2 === 0) result.q1 = (result.q1 + lowerValues[lowerIdx + 1]) / 2;

      let higherIdx = Math.floor((higherValues.length - 1) / 2);
      result.q3 = higherValues[higherIdx];
      if (higherValues.length % 2 === 0) result.q3 = (result.q3 + higherValues[higherIdx + 1]) / 2;

      result.iqr = result.q3 - result.q1;

      result.min = result.q1 - 1.5 * result.iqr;
      lowerValues.every(l => {
        if (l < result.min) {
          result.lowerOutliers.push(l);
          return true;
        } else {
          result.min = l;
          return false;
        }
      });

      result.max = result.q3 + 1.5 * result.iqr;
      higherValues.reverse().every(h => {
        if (h > result.max) {
          result.higherOutliers.push(h);
          return true;
        } else {
          result.max = h;
          return false;
        }
      });

      //result.lowerOutliers = result.lowerOutliers.filter((v, i, a) => a.indexOf(v) === i);
      //result.higherOutliers = result.higherOutliers.filter((v, i, a) => a.indexOf(v) === i);
    }

    this.boxplots.push(result);
    return result;
  }

  public fitStageIntoParentContainer() {
    let container = this.$refs.container as HTMLDivElement;
    // now we need to fit stage into parent container
    let containerWidth = container.offsetWidth;
    // but we also make the full scene visible
    // so we need to scale all objects on canvas
    /*let scale = containerWidth / this.sceneWidth;
    this.stage.width(this.sceneWidth * scale);
    this.stage.height(this.sceneHeight * scale);
    this.stage.scale({ x: scale, y: scale });*/
    this.sceneWidth = containerWidth;
    this.sceneHeight = this.sceneWidth * 0.5;
    this.stage.width(this.sceneWidth);
    this.stage.height(this.sceneHeight);

    this.redraw();
  }
}
</script>

<style lang="scss" scoped>

</style>