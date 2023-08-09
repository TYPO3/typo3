/* tslint:disable:max-classes-per-file */

/**
 * Currently a mixture between namespace and global object
 * Add types as you use them
 */
declare namespace TYPO3 {
  export let Backend: typeof import('@typo3/backend/viewport').default;
  export let ExtensionManager: typeof import('@typo3/extensionmanager/main').default;
  export let FORMEDITOR_APP: any; // @todo migrate to typescript, then use: import('@typo3/form/backend/form-editor').FormEditor;
  export let FORMMANAGER_APP: import('@typo3/form/backend/form-manager').FormManager;
  export let FormEngine: typeof import('@typo3/backend/form-engine').default;
  export let Icons: typeof import('@typo3/backend/icons').default;
  export let InfoWindow: typeof import('@typo3/backend/info-window').default;
  export let LoginRefresh: typeof import('@typo3/backend/login-refresh').default;
  export namespace ModuleMenu {
    export let App: typeof import('@typo3/backend/module-menu').default.App;
  }
  export let MultiStepWizard: typeof import('@typo3/backend/multi-step-wizard').default;
  export let Notification: typeof import('@typo3/backend/notification').default;
  export let Modal: typeof import('@typo3/backend/modal').default;
  export let LiveSearch: typeof import('@typo3/backend/toolbar/live-search').default;
  export let LiveSearchConfigurator: typeof import('@typo3/backend/live-search/live-search-configurator').default;
  export let Severity: import('@typo3/backend/severity').default;
  export let ShortcutMenu: typeof import('@typo3/backend/toolbar/shortcut-menu').default;
  export let WindowManager: typeof import('@typo3/backend/window-manager').default;
  export let Wizard: typeof import('@typo3/backend/wizard').default;
  export let WorkspacesMenu: typeof import('@typo3/workspaces/toolbar/workspaces-menu').default;
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
  TYPO3: Partial<typeof TYPO3>;
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
declare module 'flatpickr/plugins/shortcut-buttons.min';
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
