<template>
  <div  :class="{ ' edit-mode ': editMode}" >
   <strong>Tagmanager</strong><br>
    <span v-for="(tag, i) in theTags" class="tag" :class="{ ' bg-primary text-white ': tag.relID}" >
      <span @click="toggleTag(tag.tagid)">{{tag.name}}</span>
      <i  v-if="tag.relID && editMode==true"  @click="removeTag(tag.tagid)" class="fa-duotone fa-circle-trash tag-deleter"></i>
      <i  v-if="!tag.relID && editMode==true" @click="addTag(tag.tagid)" class="fa-duotone fa-circle-plus tag-deleter"></i>
    </span>
    <span v-if="!editMode" class="tag" @click="manageTags()"  style="cursor: pointer"><i class="fa-duotone fa-plus-minus"></i></span>
  </div>
</template>

<script>
import axios from "axios";
import qs from "qs";

export default {
  name: 'tageditor',

  props: {
    tags: Array,
    school: null
  },
  data() {
    return {
      theTags: this.tags,
      editMode: false
    }
  },
  mounted() {
    console.log(this.tags)
  },
  computed: {
    tagList(){
      return this.tags;
    }
  },
  methods: {


    toggleTag(tagId){
    },
     manageTags(){
       const data = {'id':this.school};
       let promise = axios.post('/Backend/School/school_tag', qs.stringify(data), {
         withCredentials: true
       });
       promise.then((data) => {
         const resp = JSON.parse(data.data);
        console.log(resp);
         if (resp.error) {
          //ERROR
         } else {
          //Fine
           this.editMode = true;
           this.theTags = resp;
         }
       }).catch(error => {
         console.error(error);
       });
     },
    addTag(tagId){
      const data = {'id':this.school,'tag':tagId,'action' : 'add'};
      let promise = axios.post('/Backend/School/school_tag', qs.stringify(data), {
        withCredentials: true
      });
      promise.then((data) => {
        const resp = JSON.parse(data.data);
        if (resp.error) {
          //ERROR
        } else {
          //Fine
          this.editMode = true;
          this.theTags = resp;

        }
      }).catch(error => {
        console.error(error);
      });
    },
    removeTag(tagId){
      const data = {'id':this.school,'tag':tagId,'action' : 'remove'};
      let promise = axios.post('/Backend/School/school_tag', qs.stringify(data), {
        withCredentials: true
      });
      promise.then((data) => {
        const resp = JSON.parse(data.data);
        console.log(resp);
        if (resp.error) {
          //ERROR
        } else {
          //Fine
          this.editMode = true;
          this.theTags = resp;

        }
      }).catch(error => {
        console.error(error);
      });
    }

  }
}
</script>

<style lang="scss" scoped>



  .tag {
    display: inline-block;
    padding: 5px;
    border: 1px solid blue;
    border-radius: 5px;
    margin: 5px;
    font-size: 10px;
    position: relative;
  }

  .edit-mode .tag{
    cursor: pointer;
  }

  .tag-deleter {
    position: absolute;
    top: -7px;
    right: -7px;
    cursor: pointer;
    font-size: 20px;
    color: black;
    background-color: white;
    display:none;
  }

  .tag:hover .tag-deleter{
    display: block;
  }
</style>
