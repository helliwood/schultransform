<template>
  <div class="hello">
    <h2>ECharts Demo</h2>
    <ul>
      <li><router-link to="/navigationchart">Navigation Chart</router-link></li>
      <li><router-link to="/gradientnavigation">Gradient Navigation</router-link></li>
<!--      <li><router-link to="/sunburstNavigation">Sunburst Navigation</router-link></li>-->
    </ul>
  </div>
</template>

<script>
export default {
  name: 'HelloWorld',
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
h1, h2 {
  font-weight: normal;
}
ul {
  list-style-type: none;
  padding: 0;
}
li {
  display: inline-block;
  margin: 0 10px;
}
a {
  color: #42b983;
}
</style>
