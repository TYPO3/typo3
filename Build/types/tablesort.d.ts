interface Tablesort {
}

declare const Tablesort: {
  new(table: Element, options?: {[key: string]: any}): Tablesort;
  extend(name: string, pattern: Function, sort: Function): void;
}
