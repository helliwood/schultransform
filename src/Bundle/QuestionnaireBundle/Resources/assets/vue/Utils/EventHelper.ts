import store from "../store/store";
import Vue from "vue";
export class EventHelper {

    public keyboardEvents: any;
    private bus: Vue;

    public constructor(bus: Vue) {
        this.bus = bus;
        this.config();
        this.initEvents();
    }

    private config() {
        this.keyboardEvents = {
            question_answer_letter: 'abcdefghijklmnopqrstuvwxyz'.split(''),
            question_answer_iteration: ['ArrowRight','ArrowLeft','ArrowUp','ArrowDown','Enter','Tab'],
            question_answer_number: ['0','1','2','3','4','5','6','7','8','9',]
        };
    }

    private initEvents() {
        window.addEventListener('keyup', (e:any) => {this.handleEvent(e)});
    }

    private handleEvent(e:any) {
        for(let key in this.keyboardEvents) {
            if(this.keyboardEvents[key].includes(e.key)) {
                //allow the user to use the 'enter' keyboard key: -> for the text inputs

                if((key == "question_answer_letter" || key == "question_answer_iteration")
                    && e.key != 'Enter'
                    && e.target.id
                    && (e.target.id == "q_other_choice" || e.target.id == "q_other_reason" ||  e.target.id == "q_free_option") ) {
                    //do not
                }else {
                    this.bus.$emit(key, e.key);
                }
            }
        };
    }



}