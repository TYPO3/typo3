interface Options {
  onClear?: (input: HTMLInputElement) => void;
}

interface HTMLInputElement {
  isClearable: boolean;
  clearable: (options?: Options) => void;
}
