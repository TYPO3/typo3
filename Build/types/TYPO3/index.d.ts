/* tslint:disable:max-classes-per-file */

/**
 * Currently a mixture between namespace and global object
 * Add types as you use them
 */
declare namespace TYPO3 {
  export let Backend: any;
  export let ExtensionManager: any;
  export let FormEngine: any;
  export let Icons: any;
  export let InfoWindow: any;
  export let LoginRefresh: any;
  export let ModuleMenu: any;
  export let MultiStepWizard: any;
  export let Notification: any;
  export let Modal: any;
  export let OpendocsMenu: any;
  export let Permissions: any;
  export let Severity: any;
  export let ShortcutMenu: any;
  export let Storage: any;
  export let Tooltip: any;
  export let WindowManager: any;
  export let Wizard: any;
  export let WorkspacesMenu: any;
  export let settings: any;
  export const lang: { [key: string]: string };
  export const configuration: any;
  export namespace CMS {
    export namespace Backend {
      // @todo transform to proper interface, once FormEngine.js is migrated to TypeScript
      export interface OnFieldChangeItem {
        name: string;
        data: {[key: string]: string|number|boolean|null}
      }
    }
  }
}

declare namespace TBE_EDITOR {
  export const customEvalFunctions: { [key: string]: (value: string) => string };
}

declare module '@typo3/ckeditor5-bundle' {
  import ClassicEditor from '@ckeditor/ckeditor5-editor-classic/src/classiceditor.js';
  export class CKEditor5 extends ClassicEditor {
    // using `any` due to type-hell
    static builtinPlugins: any[];
  }

  export * as UI from '@ckeditor/ckeditor5-ui';
  export * as Core from '@ckeditor/ckeditor5-core';
  export * as Engine from '@ckeditor/ckeditor5-engine';
  export * as Utils from '@ckeditor/ckeditor5-utils';

  export * as Clipboard from '@ckeditor/ckeditor5-clipboard';
  export * as Essentials from '@ckeditor/ckeditor5-essentials';
  export * as Link from '@ckeditor/ckeditor5-link';
  export * as LinkUtils from '@ckeditor/ckeditor5-link/src/utils';
  export * as Typing from '@ckeditor/ckeditor5-typing'
  export * as Widget from '@ckeditor/ckeditor5-widget';

  // single or prefixed exports
  export { default as LinkActionsView } from '@ckeditor/ckeditor5-link/src/ui/linkactionsview';
  export { default as WordCount } from '@ckeditor/ckeditor5-word-count/src/wordcount';
}

// type definition for global namespace object
interface Window {
  TYPO3: any;
  require: (moduleName: string) => void;
  list_frame: Window;
  CKEditorInspector: any;
}

/**
 * Needed type declarations for provided libs
 */
declare module 'muuri';
declare module 'codemirror';
declare module 'flatpickr/flatpickr.min';
declare module 'flatpickr/locales';
declare module '@typo3/backend/legacy-tree';
declare module '@typo3/install/chosen.jquery.min';
declare module '@typo3/dashboard/contrib/chartjs';
declare module '@typo3/backend/contrib/mark';

declare module '@typo3/t3editor/stream-parser/typoscript';
declare module '@typo3/t3editor/autocomplete/ts-code-completion';

interface Taboverride {
  set(elems: HTMLElement|HTMLElement[], enable?: boolean): Taboverride
}
declare module 'taboverride' {
  const _exported: Taboverride;
  export default _exported;
}
