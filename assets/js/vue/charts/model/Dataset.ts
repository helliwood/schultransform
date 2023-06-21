/* eslint-disable no-unused-vars */
import DataElement from './DataElement';

/* eslint-disable @typescript-eslint/no-explicit-any */


/**
 * Dataset contains all the Data for one PieLevel Element.
 */
export default class Dataset {
  /* eslint-disable @typescript-eslint/ban-ts-ignore */
  public sourceData: Array<DataElement>;
  public questionTags: Array<string>;
  public groupTags: Array<string>;

  /**
   * sets {@link questionTags} and {@link groupTags} by searching in sourceData for different tags. This might not be that effective but makes the programs data input very flexible.
   * @param sourceData Array of DataElements. questionTags and groupTags aren't defined at a separate place.
   */
  public constructor(sourceData: Array<DataElement>) {
    this.sourceData = sourceData;
    const myQuestionSet: Set<string> = new Set();
    const myGroupSet: Set<string> = new Set();
    this.sourceData.forEach(element => {
      myQuestionSet.add(element.questionTag);
      myGroupSet.add(element.group);
    });
    this.questionTags = Array.from(myQuestionSet);
    this.groupTags = Array.from(myGroupSet);
  }


  /**
   * filters {@link sourceData} for one group and removes the group key from the object
   * @param group
   */
  public getGroupData(group: string) {
    return this.sourceData
      .filter(element => element.group == group)
      .map(element => {
        return {questionTag: element.questionTag, answer: element.answer}
      });
  }

  /**
   * filters {@link sourceData} for one question and removes the question key from the object
   * @param question
   */
  public getQuestionData(question: string) {
    return this.sourceData
      .filter(element => element.questionTag == question)
      .map(element => {
        return {group: element.group, answer: element.answer}
      });
  }

  /**
   * Computes average from a number array
   * @param array
   */
  public static computeAverage(array: Array<number>) {
    if (array.length == 0) return 0;
    return array.reduce((a, b) => a + b, 0) / array.length;
  }

  /**
   * Gets you the answers you need. By filtering and removing keys.
   * @param questionTag optional filter
   * @param group optional filter
   * @returns an array which only contains answers
   */
  public getAnswers(questionTag?: string, group?: string) {
    let res = this.sourceData;
    if (questionTag) res = res.filter(element => element.questionTag == questionTag);
    if (group) res = res.filter(element => element.group == group);
    if (res.length < 1) console.log("Couldn't find question tag in sourceData");
    return res.map(element => element.answer);
  }


}
