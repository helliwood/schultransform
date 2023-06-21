<template>
  <div>
    <div class="mt-3 g-letter-list">
      <b-button-group size="sm" class="d-flex flex-wrap">
        <template v-for="letter in letters">
          <b-button class="mt-1 flex-grow-0 px-3 pt-2" :disabled="letter.numberOfWords<1" @click="onclicked(letter.value)">
            {{ letter.display }}
            <span v-if="letter.numberOfWords>0" class="g-letter-list-span" v-html="getNumberOfWords(letter.numberOfWords)"></span>
          </b-button>
        </template>
      </b-button-group>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "LetterList",
  props: {
    onclicked: {type: Function},
    reload: false,
  },
  data() {
    return {
      letters: null,
    }
  },
  beforeCreate() {
    axios.get('/Backend/Glossary/ajax/list').then((data) => {
      const items = data.data
      // Here we could override the busy state, setting isBusy to false
      // this.isBusy = false
      this.letters = items;
      return (items)
    }).catch(error => {
      // Here we could override the busy state, setting isBusy to false
      // this.isBusy = false
      // Returning an empty array, allows table to correctly handle
      // internal busy state in case of error
      return []
    });
  },
  mounted() {

  },
  watch: {
    reload: function (new_value, old_value) {
      if (new_value) {
        this.loadLetters();
      }

    }
  },
  computed: {
    getBtnStatus(numberOfWords) {
      if (numberOfWords < 1) {
        return true;
      }
      return false;
    }
  },
  methods: {
    getNumberOfWords:function(numberOfWords) {
      let toReturn;
      if (numberOfWords < 100) {
        toReturn = numberOfWords;
      } else {
        toReturn = '99+'
      }
      return toReturn;
    },
    getLetters() {
      return axios.get('/Backend/Glossary/ajax/list').then((data) => {
        const items = data.data
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        return (items)
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      });
    },
    loadLetters: function () {
      axios.get('/Backend/Glossary/ajax/list').then((data) => {
        const items = data.data
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        this.letters = items;
        return (items)
      }).catch(error => {
        // Here we could override the busy state, setting isBusy to false
        // this.isBusy = false
        // Returning an empty array, allows table to correctly handle
        // internal busy state in case of error
        return []
      });
    },
  },

}
</script>

<style lang="scss" scoped>
.g-letter-list {
  button {
    position: relative;
    margin-left: 5px !important;
    font-size: 1.1em;

    .g-letter-list-span {
      position: absolute;
      top: 0px;
      left: 2px;
      font-size: .7em !important;
    }

  }

  button.q-bg-gray {
    background: gray;
  }
}
</style>