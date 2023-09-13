// Type definitions for @typo3/ckeditor5-bundle
declare module '@typo3/ckeditor5-bundle' {
  import { Editor, PluginConstructor } from '@ckeditor/ckeditor5-core';
  export const CKEditor5Plugins: Record<string, PluginConstructor<Editor>>;

  import { ClassicEditor } from '@ckeditor/ckeditor5-editor-classic';
  export class CKEditor5 extends ClassicEditor {
  }

  export * as UI from '@ckeditor/ckeditor5-ui';
  export * as Core from '@ckeditor/ckeditor5-core';
  export * as Engine from '@ckeditor/ckeditor5-engine';
  export * as Utils from '@ckeditor/ckeditor5-utils';

  export * as Clipboard from '@ckeditor/ckeditor5-clipboard';
  export * as Essentials from '@ckeditor/ckeditor5-essentials';
  export * as Link from '@ckeditor/ckeditor5-link';
  export * as LinkUtils from '@ckeditor/ckeditor5-link/src/utils.js';
  export * as Typing from '@ckeditor/ckeditor5-typing'
  export * as Widget from '@ckeditor/ckeditor5-widget';

  // single or prefixed exports
  export { default as LinkActionsView } from '@ckeditor/ckeditor5-link/src/ui/linkactionsview.js';
  export { WordCount } from '@ckeditor/ckeditor5-word-count';
}

declare module '@typo3/ckeditor5-inspector' {
  export { default as CKEditorInspector } from '@ckeditor/ckeditor5-inspector';
}

// Upstream TypeScript typings are missing, see https://github.com/ckeditor/ckeditor5-inspector/issues/173
declare module '@ckeditor/ckeditor5-inspector' {
  import { Editor } from '@ckeditor/ckeditor5-core';
  export default class CKEditorInspector {
    static attach(editorOrConfig: Editor | Record<string, Editor>, options?: { isCollapsed?: boolean }): string[];
  }
}
