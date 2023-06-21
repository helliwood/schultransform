<template>
  <div class="main-menu">
    <div class="menu-btn" @click.prevent="toggleMenu" :class="{ 'open' : open }">
      <div class="menu-btn__burger"></div>
    </div>
    <div v-if="open" class="menu px-3">
      <div v-for="(item, i) in this.entries" class="item">
        <div class="links d-flex">
          <a :href="item.uri" class="w-100">{{item.label}}</a>
          <a v-if="item.children.length > 0" @click="toggleSub(i)">
            <span class="divider"></span>
            <svg v-if="subopen.includes(i)" xmlns="http://www.w3.org/2000/svg" width="30"
                 xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="Layer_1" x="0px" y="0px"
                 viewBox="0 0 330.002 330.002" style="enable-background:new 0 0 330.002 330.002;" xml:space="preserve">
<path id="XMLID_105_" fill="#dddddd"
      d="M324.001,209.25L173.997,96.75c-5.334-4-12.667-4-18,0L6.001,209.25c-6.627,4.971-7.971,14.373-3,21  c2.947,3.93,7.451,6.001,12.012,6.001c3.131,0,6.29-0.978,8.988-3.001L164.998,127.5l141.003,105.75c6.629,4.972,16.03,3.627,21-3  C331.972,223.623,330.628,214.221,324.001,209.25z"/>
</svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="30" xmlns:xlink="http://www.w3.org/1999/xlink"
                 version="1.1"
                 id="Layer_1" x="0px" y="0px" viewBox="0 0 330 330" style="enable-background:new 0 0 330 330;"
                 xml:space="preserve">
<path id="XMLID_225_" fill="#dddddd"
      d="M325.607,79.393c-5.857-5.857-15.355-5.858-21.213,0.001l-139.39,139.393L25.607,79.393  c-5.857-5.857-15.355-5.858-21.213,0.001c-5.858,5.858-5.858,15.355,0,21.213l150.004,150c2.813,2.813,6.628,4.393,10.606,4.393  s7.794-1.581,10.606-4.394l149.996-150C331.465,94.749,331.465,85.251,325.607,79.393z"/>
</svg>
          </a>
        </div>
        <div v-if="subopen.includes(i)" class="sub">
          <a v-for="sub in item.children" :href="sub.uri" class="d-block ml-3">{{sub.label}}</a>
        </div>
      </div>
      <div class="som d-flex justify-content-between py-3">
        <a href="https://twitter.com/search?q=%23Schultransform%20(from%3Ahelliwood)&src=typed_query&f=live" class="text-decoration-none text-center" target="_blank"><i class="fab fa-twitter p-2"></i><br />@helliwood</a>
        <a href="https://twitter.com/search?q=%23Schultransform%20(from%3ABfBildung)&src=typed_query&f=live" class="text-decoration-none text-center" target="_blank"><i class="fab fa-twitter p-2"></i><br />@BfBildung</a>
        <a href="https://www.linkedin.com/showcase/schultransform" class="text-decoration-none text-center" target="_blank"><i class="fab fa-linkedin-in p-2"></i><br />Schultransform</a>
        <a href="https://vimeo.com/showcase/schultransformation" class="text-decoration-none text-center" target="_blank"><i class="fab fa-vimeo-v p-2"></i><br />Mediathek</a>
      </div>
    </div>
    <div v-if="open" class="backdrop" @click.prevent="close"></div>
  </div>
</template>

<script>
export default {
  name: 'main-menu',
  props: {
    entries: Array
  },
  data() {
    return {
      open: false,
      subopen: []
    }
  },
  mounted() {
  },
  methods: {
    toggleMenu() {
      console.log("toggle menu");
      this.open = !this.open;
    },
    close() {
      this.open = false;
    },
    toggleSub(i) {
      console.log(i);
      if (this.subopen.includes(i)) {
        delete this.subopen.splice(this.subopen.indexOf(i), 1);
      } else {
        this.subopen.push(i);
      }
      console.log(this.subopen);
    }
  }
}
</script>

<style lang="scss" scoped>
.main-menu {

  .menu-btn {
    z-index: 10002;
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 80px;
    height: 80px;
    cursor: pointer;
    transition: all .5s ease-in-out;
    /* border: 3px solid #fff; */
  }

  .menu-btn__burger {
    width: 50px;
    height: 6px;
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(255, 101, 47, .2);
    transition: all .2s ease-in-out;
  }

  .menu-btn__burger::before,
  .menu-btn__burger::after {
    content: '';
    position: absolute;
    width: 50px;
    height: 6px;
    background: #fff;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(255, 101, 47, .2);
    transition: all .2s ease-in-out;
  }

  .menu-btn__burger::before {
    transform: translateY(-16px);
  }

  .menu-btn__burger::after {
    transform: translateY(16px);
  }

  /* ANIMATION */
  .menu-btn.open .menu-btn__burger {
    transform: translateX(-50px);
    background: transparent;
    box-shadow: none;
  }

  .menu-btn.open .menu-btn__burger::before {
    transform: rotate(45deg) translate(35px, -35px);
  }

  .menu-btn.open .menu-btn__burger::after {
    transform: rotate(-45deg) translate(35px, 35px);
  }

  .menu {
    position: absolute;
    top: 80px;
    color: #000;
    min-width: 450px;
    background-color: #fff;
    z-index: 10001;
    line-height: 70px;

    .item {
      border-bottom: 1px solid #dddddd;

      .links {
        white-space: nowrap;

        a {
          font-weight: bold;
          cursor: pointer;

          .divider {
            border-left: 1px solid #dddddd;
            margin-right: 10px;
          }
        }
      }

      .sub {
        line-height: 50px;
      }
    }
    .som {
      a {
        color: #909090;
        line-height: 40px;
        font-size: 0.8rem;
        i {
          border: 1px solid #909090;
          border-radius: 50%;
          font-size: 1.5rem;
        }
      }
      a:hover {
        color: #006292;
        i {
          border-color: #006292;
        }
      }
    }
  }

  @media only screen and (max-width: 575px) {
    .menu {
      width: 100vw;
      min-width: auto;
    }
  }

  .backdrop {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 10000;
    background-color: rgba(0, 0, 0, 0.2);
  }
}
</style>