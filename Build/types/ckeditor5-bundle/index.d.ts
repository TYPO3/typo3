declare module '@ckeditor/ckeditor5-link' {
  export * from '@ckeditor/ckeditor5-link/src/index.js';
  export * as LinkUtils from '@ckeditor/ckeditor5-link/src/utils.js';
}

// Upstream TypeScript typings are missing, see https://github.com/ckeditor/ckeditor5-inspector/issues/173
declare module '@ckeditor/ckeditor5-inspector' {
  import type { Editor } from '@ckeditor/ckeditor5-core';
  export default class CKEditorInspector {
    static attach(editorOrConfig: Editor | Record<string, Editor>, options?: { isCollapsed?: boolean }): string[];
  }
}
