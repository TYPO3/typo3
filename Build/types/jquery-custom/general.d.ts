interface JQueryStatic {
  escapeSelector(selector: string): string;
}

interface JQueryTypedEvent<T extends Event> extends JQueryEventObject {
  originalEvent: T;
}
