export type TablesortOptions = {
  descending: boolean;
  sortAttribute: string;
};

export default class Tablesort {
  protected table: HTMLTableElement;
  protected thead: boolean;
  protected options: TablesortOptions;
  protected current: HTMLTableCellElement;

  constructor(table: HTMLElement, options?: {[key: string]: TablesortOptions});
  protected sortTable(table: HTMLTableCellElement): void;
}
