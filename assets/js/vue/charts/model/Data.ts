/**
 * Just the data for PieLevel Series. By adjusting value you can change the size of the representation in the Navigation Element.
 */
export default class Data {
  /** @default 100 */
  value: number;
  name: string;

  constructor (value: number, name: string) {
    this.value = value;
    this.name = name;
  }
}