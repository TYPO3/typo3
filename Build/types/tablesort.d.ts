// eslint-disable-next-line @typescript-eslint/no-empty-interface
interface Tablesort {
}

type TablesortOptions = {
  descending: boolean;
  sortAttribute: string;
};

declare const Tablesort: {
  new(table: Element, options?: {[key: string]: TablesortOptions}): Tablesort;
  // eslint-disable-next-line @typescript-eslint/ban-types
  extend(name: string, pattern: Function, sort: Function): void;
}

declare module 'tablesort' {
  export default Tablesort;
}
