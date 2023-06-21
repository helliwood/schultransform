export class QuestionnaireHelper{

    public constructor() {
    }

    public getLetters ():any{
        return 'abcdefghijklmnopqrstuvwxyz'.split('');
    }

    public removeItemInArrayByValue(arrayData:any,key:string){
        return arrayData.filter( (letter:any) => letter != key);
    }
    public removeItemOtherChoice(arrayData:any,key:any){
        return arrayData.filter( (letter:any) => letter != key);
    }
}