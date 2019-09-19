interface Options {
  onClear?: Function;
}

interface HTMLInputElement {
  isClearable: boolean;
  clearable: (options?: Options) => void;
}
