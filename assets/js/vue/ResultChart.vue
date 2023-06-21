<template>
  <div :style="{minHeight:this.minHeight+'px'}">
    <b-form-checkbox
        v-model="onlySchools"
        name="schools"
        :value="true"
        :unchecked-value="false"
        switch>
      Nur Schulen
    </b-form-checkbox>
    <div class="row position-relative">
      <div v-for="category in this.data" class="category col-12 col-md-6 col-lg-4"
           @click="openCategory($event, category)">
        <result-chart-gauge :chart-value="onlySchools ? category.schoolValue : category.value" :chart-color="category.color"></result-chart-gauge>
        <div class="box" :class="{open:selectedCategory === category}">
          <span><i :class="category.icon" :style="{color:category.color}"></i></span>
          {{ category.name }}
          <i class="fad fa-chevron-up colla-indicator"></i>
        </div>
      </div>
      <div v-if="selectedCategory" class="layer" ref="layer" :style="{top:this.layerY+'px'}">
        <div class="close" @click="closeCategory"></div>
        <div class="row">
          <div v-for="questionnaire in this.selectedCategory.questionnaires"
               class="subcategory col-12 col-md-6 col-lg-4">
            <result-chart-gauge :chart-value="onlySchools ? questionnaire.schoolValue : questionnaire.value"
                                :chart-color="selectedCategory.color"></result-chart-gauge>
            <div class="box"><span><i :class="questionnaire.icon" :style="{color:selectedCategory.color}"></i></span>
              {{ questionnaire.name }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import ResultChartGauge from "./ResultChartGauge";

export default {
  name: "result-chart",
  components: {ResultChartGauge},
  props: {
    apiUrl: null,
    staticData: null
  },
  data: function () {
    return {
      onlySchools: true,
      data: [],
      selectedCategory: null,
      layerY: 100,
      minHeight: 0
    }
  },
  mounted() {
    console.log('staticData: ');
    console.log(this.staticData);

    if (this.staticData) {
      this.data = this.staticData;
    } else {
      axios.get(this.apiUrl).then(response => {
        this.data = response.data;
        console.log('apiUrl: ');
        console.log(this.data);
      });
    }
  },
  methods: {
    openCategory: function (event, category) {
      if (category === this.selectedCategory) {
        this.selectedCategory = null;
      } else {
        this.selectedCategory = category;
        this.$nextTick(() => {
          this.layerY = event.currentTarget.offsetTop + event.currentTarget.offsetHeight;
          this.minHeight = this.layerY + this.$refs.layer.offsetHeight + 15 + 25;

          this.$refs.layer.style.height = "auto";
        });
      }
    },
    closeCategory: function (event) {
      this.selectedCategory = null;
      this.minHeight = 0;
    }
  }
}
</script>

<style lang="scss" scoped>
@import "assets/scss/frontend/vars";

.category {
  position: relative;
  cursor: pointer;

  .box {
    font-weight: bold;
    background-color: #f2f2f2;
    padding: $grid-gutter-width;
    position: relative;

    .colla-indicator {
      transform: rotate(0deg);
      transition: all 0.5s;
    }

    &.open {
      &::after {
        content: "";
        display: block;
        background-color: #f2f2f2;
        position: absolute;
        height: 20px;
        left: 0;
        right: 0;
        bottom: -20px;
      }

      .colla-indicator {
        transform: rotate(180deg);
      }
    }
  }
}

.layer {
  z-index: 100;
  position: absolute;
  left: 0px;
  right: 0px;
  border: $grid-gutter-width solid #f2f2f2;
  background-color: #fff;
  margin: $grid-gutter-width/2;
  padding: $grid-gutter-width/2;

  .box {
    text-align: center;
  }

  .close {
    z-index: 100;
    cursor: pointer;
    position: absolute;
    right: 20px;
    top: 20px;
    width: 32px;
    height: 32px;
    opacity: 0.3;
  }

  .close:hover {
    opacity: 1;
  }

  .close:before, .close:after {
    position: absolute;
    left: 15px;
    content: ' ';
    height: 33px;
    width: 2px;
    background-color: #333;
  }

  .close:before {
    transform: rotate(45deg);
  }

  .close:after {
    transform: rotate(-45deg);
  }
}
</style>