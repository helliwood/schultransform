/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you require will output into a single css file (app.css in this case)

import "babel-polyfill";
import {BootstrapVue} from 'bootstrap-vue'
import {Vue} from "vue-property-decorator";
import GradientNavigation from "./vue/charts/components/GradientNavigation.vue";
import NavigationChart from "./vue/charts/components/NavigationChart.vue";
import store from './vue/charts/store/index';
import Login from './vue/login';
import SearchSchoolAuthorities from './vue/SearchSchoolAuthorities';
import UserStatus from './vue/header/userstatus';
import charttest from "./vue/charts/components/charttest";
import Iframe from './vue/iframe';
import MainMenu from './vue/MainMenu';
import ResultChart from "./vue/ResultChart";
import ResultChartGauge from "./vue/ResultChartGauge";
import RegisterSchoolAuthorities from "./vue/RegisterSchoolAuthorities";
import GlossaryLink from "./vue/glossary/GlossaryLink";
import FormManipulator from "./vue/FormManipulator";
import ResultChartBoxplot from "./vue/ResultChartBoxplot";
import Boxplot from "./vue/Boxplot";
import DashboardModal from "./vue/dashboard/DashboardModal";

const $ = require('jquery');
require('../scss/frontend.scss');
require('bootstrap');
require('../../src/Bundle/QuestionnaireBundle/Resources/assets/js/frontend.js');
require('../../vendor/trollfjord/cookie-bundle/Resources/assets/js/frontend.js')

Vue.use(BootstrapVue);
Vue.use(require('vue-cookies'));
Vue.component(FormManipulator.name, FormManipulator);
Vue.component(Login.name, Login);
Vue.component(MainMenu.name, MainMenu);
Vue.component(SearchSchoolAuthorities.name, SearchSchoolAuthorities);
Vue.component(RegisterSchoolAuthorities.name, RegisterSchoolAuthorities);
Vue.component(UserStatus.name, UserStatus);
Vue.component(Iframe.name, Iframe);
Vue.component('v-chart', charttest);
Vue.component(ResultChart.name, ResultChart);
Vue.component(ResultChartGauge.name, ResultChartGauge);
Vue.component(ResultChartBoxplot.name, ResultChartBoxplot);
Vue.component("boxplot", Boxplot);
Vue.component(GlossaryLink.name, GlossaryLink);
Vue.component(DashboardModal.name, DashboardModal);

new Vue({
    store,
    el: '#app',
    components: {
        GradientNavigation,
        NavigationChart,
    },
});

$('.nav-trigger').click(function (e) {
    e.preventDefault();
    $('#icon-nav').toggleClass('open');
});

window.onload = function () {
    externalLinks();
}


function externalLinks() {
    var links = document.getElementById('main-content').getElementsByTagName('a');

    for (var i = 0, linksLength = links.length; i < linksLength; i++) {
        if (links[i].hostname != window.location.hostname && links[i].href != "javascript:void(0)") {
            links[i].target = '_blank';
            links[i].classList.add("ext-link");
            links[i].innerHTML += ' <i class="fad fa-external-link-alt"></i> '

        }
    }
}
