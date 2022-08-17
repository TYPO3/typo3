import ClassicEditor from '@ckeditor/ckeditor5-editor-classic/src/classiceditor.js';
import BlockQuote from '@ckeditor/ckeditor5-block-quote/src/blockquote.js';
import Essentials from '@ckeditor/ckeditor5-essentials/src/essentials.js';
import FindAndReplace from '@ckeditor/ckeditor5-find-and-replace/src/findandreplace';
import Heading from '@ckeditor/ckeditor5-heading/src/heading.js';
import Indent from '@ckeditor/ckeditor5-indent/src/indent.js';
import Link from '@ckeditor/ckeditor5-link/src/link.js';
import List from '@ckeditor/ckeditor5-list/src/list.js';
import Paragraph from '@ckeditor/ckeditor5-paragraph/src/paragraph.js';
import PastePlainText from '@ckeditor/ckeditor5-clipboard/src/pasteplaintext.js';
import PasteFromOffice from '@ckeditor/ckeditor5-paste-from-office/src/pastefromoffice.js';
import RemoveFormat from '@ckeditor/ckeditor5-remove-format/src/removeformat.js';
import Table from '@ckeditor/ckeditor5-table/src/table.js';
import TableToolbar from '@ckeditor/ckeditor5-table/src/tabletoolbar.js';
import TableProperties from '@ckeditor/ckeditor5-table/src/tableproperties.js';
import TableCellProperties from '@ckeditor/ckeditor5-table/src/tablecellproperties';
import TextTransformation from '@ckeditor/ckeditor5-typing/src/texttransformation.js';
import SourceEditing from '@ckeditor/ckeditor5-source-editing/src/sourceediting';
import Alignment from '@ckeditor/ckeditor5-alignment/src/alignment'
import Style from '@ckeditor/ckeditor5-style/src/style';
import GeneralHtmlSupport from '@ckeditor/ckeditor5-html-support/src/generalhtmlsupport';
import { Bold, Italic, Subscript, Superscript, Strikethrough, Underline } from '@ckeditor/ckeditor5-basic-styles/src';
import { SpecialCharacters, SpecialCharactersEssentials } from '@ckeditor/ckeditor5-special-characters/src';
import { HorizontalLine } from '@ckeditor/ckeditor5-horizontal-line/src';
import { WordCount } from '@ckeditor/ckeditor5-word-count/src';

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
  List,
  Paragraph,
  PastePlainText,
  PasteFromOffice,
  RemoveFormat,
  SpecialCharacters,
  SpecialCharactersEssentials,
  SourceEditing,
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

export class CKEditor5 extends ClassicEditor {}
CKEditor5.builtinPlugins = Object.values(CKEditor5Plugins);

export * as Core from '@ckeditor/ckeditor5-core/src/index.js';
export * as UI from '@ckeditor/ckeditor5-ui/src/index.js';
export * as Engine from '@ckeditor/ckeditor5-engine/src/index.js';
export * as Utils from '@ckeditor/ckeditor5-utils/src/index';

export * as Clipboard from '@ckeditor/ckeditor5-clipboard/src/index.js';
export * as Essentials from '@ckeditor/ckeditor5-essentials/src/index.js';
export * as Link from '@ckeditor/ckeditor5-link/src/index.js';
export * as LinkUtils from '@ckeditor/ckeditor5-link/src/utils.js';

export * as Typing from '@ckeditor/ckeditor5-typing/src/index.js'
export * as Widget from '@ckeditor/ckeditor5-widget/src/index.js';

// single or prefixed exports
export { default as WordCount } from '@ckeditor/ckeditor5-word-count/src/wordcount.js';
