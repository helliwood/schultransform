<template>
  <!-- PROF OF CONCEPT - Maurice Karg -->
  <div>
    <svg id="pie-chart" viewBox="-1 -1 2 2">
      <path v-for="slice in this.slices" :d="slice.path" :fill="slice.color">
      </path>
    </svg>
    <button @click="this.updateValues">Change</button>
    <ul>
      <li>Radius = 85</li>
      <li>Percentage to fill = 25</li>
      <li>Circumference = 2 * π * Radius = 534</li>
      <li>Stroke length = (Circumference / 100) * Percentage to fill</li>
    </ul>
    <svg class="circle-chart" viewbox="0 0 180 180" width="180" height="180" xmlns="http://www.w3.org/2000/svg">
      <circle class="circle-chart__background" stroke="#efefef" stroke-width="10" fill="none" cx="90" cy="90" r="85"/>
      <circle class="circle-chart__circle" stroke="#00acc1" stroke-width="10" style="border: 1px solid #000;"
              stroke-dasharray="134,534" transform="rotate(-90, 90, 90)" stroke-linecap="round" fill="none" cx="90"
              cy="90" r="85"/>
    </svg>
  </div>
</template>

<script>
import anime from "animejs/lib/anime"

export default {
  name: "pie-chart",
  props: {
    radius: {type: Number, default: 0.75},
  },
  data: function () {
    return {
      slices: null
    }
  },
  mounted() {
    console.log(this.radius);
    setTimeout(() => {
      this.slices = [
        {percent: 0.0, percentFrom: 0.0, percentTo: 0.1, color: 'Coral', path: null},
        {percent: 0.0, percentFrom: 0.0, percentTo: 0.65, color: 'CornflowerBlue', path: null},
        {percent: 0.0, percentFrom: 0.0, percentTo: 0.25, color: '#00ab6b', path: null},
      ];
    }, 500);
  },
  watch: {
    slices: function (newSlices, oldSlices) {
      console.log(newSlices, oldSlices);
      this.slices = newSlices;
      this.update();
    }
  },
  methods: {
    update: function () {
      var Counter = {
        percent: 0,
      }
      var self = this;
      anime({
        targets: Counter,
        percent: 1,
        easing: 'linear',
        duration: 300,
        update: function () {
          let cumulativePercent = 0;
          self.slices.forEach(slice => {
            console.log(slice);
            if (slice.percent == slice.percentTo) {
              cumulativePercent += slice.percent;
            } else {
              let change = (slice.percentTo - slice.percentFrom) * Counter.percent;
              slice.percent = slice.percentFrom + change;

              const [startX, startY] = self.getCoordinatesForPercent(cumulativePercent);

              cumulativePercent += slice.percent;

              const [endX, endY] = self.getCoordinatesForPercent(cumulativePercent);

              // if the slice is more than 50%, take the large arc (the long way around)
              const largeArcFlag = slice.percent > .5 ? 1 : 0;

              // create an array and join it just for code readability
              const pathData = [
                `M ${startX} ${startY}`, // Move
                `A ${self.radius} ${self.radius} 0 ${largeArcFlag} 1 ${endX} ${endY}`, // Arc
                `L 0 0`,// Line
                `L ${startX} ${startY}`,// Line
              ].join(' ');
              slice.path = pathData;
            }
          });
        }
      });
    },
    updateValues: function () {
      this.slices = [
        {percent: 0.0, percentFrom: 0.0, percentTo: 0.10, color: '#000000', path: null},
        {percent: 0.1, percentFrom: 0.1, percentTo: 0.4, color: 'red', path: null},
        {percent: 0.65, percentFrom: 0.65, percentTo: 0.35, color: 'CornflowerBlue', path: null},
        {percent: 0.25, percentFrom: 0.25, percentTo: 0.15, color: '#00ab6b', path: null},
      ];
    },
    getCoordinatesForPercent: function (percent) {
      const x = this.radius * Math.cos(2 * Math.PI * percent);
      const y = this.radius * Math.sin(2 * Math.PI * percent);
      return [x, y];
    }
  }
}
</script>

<style lang="scss" scoped>
#pie-chart {
  height: 200px;
  transform: rotate(-90deg);

  path {
    stroke: #FFF;
    stroke-width: 0.01;
    /*transition: 2s linear;*/
  }
}
</style>