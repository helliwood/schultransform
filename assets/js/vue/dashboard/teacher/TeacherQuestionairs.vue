<template>
  <div>
    <div class="mb-4">
      <div class="mt-1 mb-2 tr-line-modal">
        <p>{{ title }}</p>
      </div>

      <p>{{ intro }}</p>

      <div v-for="item in this.data">

        <div class="questionaire-overlay-wrapper d-flex my-2">
          <div :class="'questionaire-overlay-icon bg-category-'+item.id ">
            <i :class="'text-white fad '+item.icon"></i>
          </div>

          <div v-if="item.userHasQuestionnairesFilledOut" class="col pl-1 pr-0 tr-sm-w-10 w-sm-100">
            <div :class="'pl-1  category-'+item.id+' border-category-'+item.id">
              <p>
              <span class="tr-ellipsis">
                {{ item.name }}
              </span>
              </p>
            </div>
            <!--            <span class="smaller-font d-block  pb-1 light-subtitle-color">Stand: 18.08.2022</span>-->
          </div>

          <div v-if="!item.userHasQuestionnairesFilledOut" class="col pl-1 pr-0 tr-sm-w-10 w-sm-100">
            <div :class="'pl-1 border-category-'+item.id">
              <p>
              <span class="tr-ellipsis">
              {{ item.name }}
              </span>
              </p>
            </div>

            <!--            <span class="smaller-font d-block  pb-1 light-subtitle-color">{{ texts[2] }}</span>-->
          </div>

          <a href="#" :id="'parent_'+item.id" v-if="item.subcats" class="catButton questionaire-overlay-button"
             @click="showSubCats(item)">
            <span v-if="item.userHasQuestionnairesFilledOut" :class="'d-block border-category-'+item.id">
              <span v-if="item.numberOfQuestionnairesFilledOut < item.questionnairesInCategory">{{ texts[0] }}</span>
              <span v-else>{{ texts[3] }}</span>
            </span>
            <span v-if="!item.userHasQuestionnairesFilledOut"
                  :class="'d-block border-category-'+item.id+' category-'+item.id">{{ texts[1] }}</span>
          </a>
          <div :id="'arrow_'+item.id" :class="'c-'+item.id" class="ml-2 tr-display-none tr-arrow"
               @click="hideSubCats()">
            <a href="#" class="p-2"><i
                class="fad fa-chevron-up tr-display-4 tr-cat-color"></i></a>
          </div>

          <a v-if="!item.subcats" :href="'/Dashboard/Teacher/nextQuestionair/'+item.id"
             class="questionaire-overlay-button">
            <span v-if="item.userHasQuestionnairesFilledOut" :class="'d-block border-category-'+item.id">
              <span v-if="item.numberOfQuestionnairesFilledOut < item.questionnairesInCategory">{{ texts[0] }}</span>
              <span v-else>{{ texts[3] }}</span>
            </span>
            <span v-if="!item.userHasQuestionnairesFilledOut"
                  :class="'d-block border-category-'+item.id+' category-'+item.id">{{ texts[1] }}</span>
          </a>
        </div>


        <div style="display:none" :id="'subcat'+item.id" class="catWrapper">

          <div v-for="subcat in item.subcats" class="d-flex flex-column">
            <div class="questionaire-overlay-wrapper d-flex ml-grid">
              <div :class="'questionaire-overlay-icon bg-category-'+item.id ">
                <i :class="'text-white fad '+subcat.icon"></i>
              </div>
              <div v-if="subcat.doneat" class="col pl-1 pr-0 tr-sm-w-10 w-sm-100">
                <div :class="'pl-1  category-'+item.id+' border-category-'+item.id">
                  <p>
              <span class="tr-ellipsis">
                {{ subcat.name }}
              </span>
                  </p>
                </div>
              </div>
              <div v-else class="col pl-1 pr-0 tr-sm-w-10 w-sm-100">
                <div :class="'pl-1 border-category-'+item.id">
                  <p>
              <span class="tr-ellipsis">
                {{ subcat.name }}
              </span>
                  </p>
                </div>
              </div>
              <a :href="subcat.slug" class="questionaire-overlay-button">
                <span v-if="subcat.doneat" :class="'d-block border-category-'+item.id">Wiederholen</span>
                <span v-if="!subcat.doneat"
                      :class="'d-block border-category-'+item.id+' category-'+item.id">Starten</span>
              </a>
            </div>
            <div>
              <span v-if="subcat.doneat"
                    class="smaller-font d-block pb-1 light-subtitle-color ml-grid">Stand: {{ subcat.doneat }}</span>
              <span v-else class="smaller-font d-block pb-1 light-subtitle-color ml-grid">noch nicht gestartet</span>
            </div>

          </div>
        </div>


      </div>

    </div>
  </div>
</template>

<script>
export default {
  name: "TeacherQuestionairs",
  props: {
    data: null,
    id: null,
    category: null,
    texts: null,
    intro: null,
    title: null
  },
  mounted() {
    if (this.texts === null) {
      this.texts = ['starten', 'fortfahren', 'noch nicht gestartet']
    }
  },
  computed: {
    getCode() {
      if (this.data && (this.data.code)) {
        return this.data.code;
      }
    }
  },
  methods: {
    hideSubCats: function () {
      document.querySelectorAll('.catButton').forEach(box => {
        box.style.display = 'block';
      });
      document.querySelectorAll('.catWrapper').forEach(box => {
        box.style.display = 'none';
      });
      document.querySelectorAll('.tr-arrow').forEach(box => {
        box.classList.add('tr-display-none');
      });
    },
    showSubCats: function (item) {
      document.querySelectorAll('.tr-arrow').forEach(box => {
        box.classList.add('tr-display-none');
      });
      document.querySelectorAll('.catButton').forEach(box => {
        box.style.display = 'block';
      });
      document.querySelectorAll('.catWrapper').forEach(box => {
        box.style.display = 'none';
      });
      document.getElementById('subcat' + item.id).style.display = 'block';

      let parent = document.getElementById('parent_' + item.id);
      parent.style.display = 'none';
      let arrow = document.getElementById('arrow_' + item.id);
      arrow.classList.remove('tr-display-none')

      return false;
    },
  },
}
</script>

<style lang="scss" scoped>
.tr-display-none {
  display: none;
}

</style>
