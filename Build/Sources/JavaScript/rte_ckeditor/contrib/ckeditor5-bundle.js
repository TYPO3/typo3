import { ClassicEditor } from '@ckeditor/ckeditor5-editor-classic';
import { BlockQuote } from '@ckeditor/ckeditor5-block-quote';
import { Essentials } from '@ckeditor/ckeditor5-essentials';
import { FindAndReplace } from '@ckeditor/ckeditor5-find-and-replace';
import { Heading } from '@ckeditor/ckeditor5-heading';
import { Indent } from '@ckeditor/ckeditor5-indent';
import { Link } from '@ckeditor/ckeditor5-link';
import { DocumentList } from '@ckeditor/ckeditor5-list';
import { Paragraph } from '@ckeditor/ckeditor5-paragraph';
import { PastePlainText } from '@ckeditor/ckeditor5-clipboard';
import { PasteFromOffice } from '@ckeditor/ckeditor5-paste-from-office';
import { RemoveFormat } from '@ckeditor/ckeditor5-remove-format';
import { Table, TableToolbar, TableProperties, TableCellProperties } from '@ckeditor/ckeditor5-table';
import { TextTransformation } from '@ckeditor/ckeditor5-typing';
import { SourceEditing } from '@ckeditor/ckeditor5-source-editing';
import { ShowBlocks } from '@ckeditor/ckeditor5-show-blocks';
import { Alignment } from '@ckeditor/ckeditor5-alignment'
import { Style } from '@ckeditor/ckeditor5-style';
import { GeneralHtmlSupport } from '@ckeditor/ckeditor5-html-support';
import { Bold, Italic, Subscript, Superscript, Strikethrough, Underline } from '@ckeditor/ckeditor5-basic-styles';
import { SpecialCharacters, SpecialCharactersEssentials } from '@ckeditor/ckeditor5-special-characters';
import { HorizontalLine } from '@ckeditor/ckeditor5-horizontal-line';
import { WordCount } from '@ckeditor/ckeditor5-word-count';

export const CKEditor5Plugins = {
  Alignment,
  BlockQuote,
  Bold,
  Essentials,
  FindAndReplace,
  GeneralHtmlSupport,
  Heading,
  HorizontalLine,
  Indent,
  Italic,
  Link,
  DocumentList,
  Paragraph,
  PastePlainText,
  PasteFromOffice,
  RemoveFormat,
  SpecialCharacters,
  SpecialCharactersEssentials,
  SourceEditing,
  ShowBlocks,
  Style,
  Subscript,
  Superscript,
  Strikethrough,
  Table,
  TableToolbar,
  TableProperties,
  TableCellProperties,
  TextTransformation,
  Underline,
  WordCount,
};

export class CKEditor5 extends ClassicEditor {
  static builtinPlugins = Object.values(CKEditor5Plugins);
}

export * as Core from '@ckeditor/ckeditor5-core';
export * as UI from '@ckeditor/ckeditor5-ui';
export * as Engine from '@ckeditor/ckeditor5-engine';
export * as Utils from '@ckeditor/ckeditor5-utils';

export * as Clipboard from '@ckeditor/ckeditor5-clipboard';
export * as Essentials from '@ckeditor/ckeditor5-essentials';
export * as Link from '@ckeditor/ckeditor5-link';
export * as LinkUtils from '@ckeditor/ckeditor5-link/src/utils.js';

export * as Typing from '@ckeditor/ckeditor5-typing'
export * as Widget from '@ckeditor/ckeditor5-widget';

// single or prefixed exports
export { default as LinkActionsView } from '@ckeditor/ckeditor5-link/src/ui/linkactionsview';
export { WordCount } from '@ckeditor/ckeditor5-word-count';
