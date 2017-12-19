/* tslint:disable:max-classes-per-file */

/**
 * Currently a mixture between namespace and global object
 * Add types as you use them
 */
declare namespace TYPO3 {
  export let DebugConsole: any;
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

declare module 'TYPO3/CMS/Backend/Severity' {
  export = new TYPO3.CMS.Backend.Severity();
}

// type definition for global namespace object
interface Window {
  TYPO3: any;
  $: any;
}

/**
 * Needed type declarations for provided libs
 */
declare module 'TYPO3/CMS/Core/Contrib/imagesloaded.pkgd.min' {
  import * as imagesloaded from 'imagesloaded';
  export = imagesloaded;
}

declare module 'cm/lib/codemirror';

/**
 * Required to make jQuery plugins "available" in TypeScript
 */
interface JQuery {
  clearable(): JQuery;
}
