/* tslint:disable:max-classes-per-file */

/**
 * Currently a mixture between namespace and global object
 * Add types as you use them
 */
declare namespace TYPO3 {
  export let DebugConsole: any;
  export let InfoWindow: any;
  export let Popover: any;
  export let ShortcutMenu: any;
  export let Storage: any;
  export let Utility: any;
  export const lang: any;
  export const settings: any;
  export namespace CMS {
    export namespace Backend {
      export class FormEngineValidation {
        public readonly errorClass: string;
      }
      export class FormEngine {
        public readonly Validation: FormEngineValidation;
      }
      export class Modal {
        public readonly sizes: {[key: string]: string};
        public readonly styles: {[key: string]: string};
        public readonly types: {[key: string]: string};
        public currentModal: JQuery;
        public advanced(configuration: object): any;
        public confirm(title: string, content: any, severity: number, buttons: any[], additionalCssClasses?: string[]): JQuery; // tslint:disable-line:max-line-length
        public show(title: string, content: any, severity: number, buttons: any[], additionalCssClasses?: string[]): JQuery; // tslint:disable-line:max-line-length
        public dismiss(): void;
      }
      export class Notification {
        public readonly Notification: {
          NOTICE: -2,
          INFO: -1,
          OK: 0,
          WARNING: 1,
          ERROR: 2
        };
        public notice(title: string, message: string, duration: Number): string;
        public info(title: string, message: string, duration: Number): string;
        public success(title: string, message: string, duration: Number): string;
        public warning(title: string, message: string, duration: Number): string;
        public error(title: string, message: string, duration: Number): string;
        public showMessage(title: string, message: string, severity: Number, duration?: Number): string;
      }
      export class Severity {
        public readonly notice: number;
        public readonly info: number;
        public readonly ok: number;
        public readonly warning: number;
        public readonly: number;
        public getCssClass(severity: number): string;
      }
    }
  }
}

/**
 * Current AMD/RequireJS modules are returning *instances* of ad-hoc *classes*, make that known to TypeScript
 */

declare module 'TYPO3/CMS/Backend/FormEngineValidation' {
  export = new TYPO3.CMS.Backend.FormEngineValidation();
}

declare module 'TYPO3/CMS/Backend/FormEngine' {
  export = new TYPO3.CMS.Backend.FormEngine();
}

declare module 'TYPO3/CMS/Backend/Modal' {
  export = new TYPO3.CMS.Backend.Modal();
}

declare module 'TYPO3/CMS/Backend/Notification' {
  export = new TYPO3.CMS.Backend.Notification();
}

declare module 'TYPO3/CMS/Backend/Severity' {
  export = new TYPO3.CMS.Backend.Severity();
}

// type definition for global namespace object
interface Window {
  TYPO3: any;
  $: any;
  inline: {
    delayedImportElement: (objectId: number, table: string, uid: number, type: string) => void
  };
}

/**
 * Needed type declarations for provided libs
 */
declare module 'TYPO3/CMS/Core/Contrib/imagesloaded.pkgd.min' {
  import * as imagesloaded from 'imagesloaded';
  export = imagesloaded;
}

declare module 'cm/lib/codemirror';
declare module 'moment';
declare module 'TYPO3/CMS/Backend/jsfunc.inline';

/**
 * Options for the plugin.
 * TODO fix this
 */
interface DragUploaderOptions {
  /**
   * CSS selector for the element where generated messages are inserted. (required)
   */
  outputSelector: string;
  /**
   * Color of the message text. (optional)
   */
  outputColor?: string;
}

interface JQueryTypedEvent<T extends Event> extends JQueryEventObject {
  originalEvent: T;
}

/**
 * Required to make jQuery plugins "available" in TypeScript
 */
interface JQuery {
  clearable(): JQuery;
  dragUploader(options?: DragUploaderOptions): JQuery;
}
