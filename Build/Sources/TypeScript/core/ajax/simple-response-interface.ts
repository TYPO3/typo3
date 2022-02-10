export default interface SimpleResponseInterface {
  status: number;
  headers: Map<string, string>;
  body: string | any;
}
