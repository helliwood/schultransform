export default class {
  public group: string;
  public questionTag: string;
  public answer: number;

	constructor(group: string, questionTag: string, answer: number) {
    this.group = group;
    this.questionTag = questionTag;
    this.answer = answer;
  }
}