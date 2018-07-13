/* tslint:disable:max-classes-per-file */

/**
 * Currently a mixture between namespace and global object
 * Add types as you use them
 */
declare namespace TYPO3 {
  export let Backend: any;
  export let DebugConsole: any;
  export let Icons: any;
  export let InfoWindow: any;
  export let LoginRefresh: any;
  export let ModuleMenu: any;
  export let Notification: any;
  export let Modal: any;
  export let Popover: any;
  export let Severity: any;
  export let ShortcutMenu: any;
  export let Storage: any;
  export let Utility: any;
  export const lang: any;
  export const settings: any;
  export const configuration: any;
  export namespace CMS {
    export namespace Backend {
      export class FormEngineValidation {
        public readonly errorClass: string;
      }

      export class FormEngine {
        public readonly Validation: FormEngineValidation;
      }

      export class Wizard {
        public addSlide(identifier: string, title: string, content: string, severity: number, callback?: Function): Wizard;
        public lockNextStep(): JQuery;
        public unlockNextStep(): JQuery;
        public getComponent(): JQuery;
        public addFinalProcessingSlide(callback?: Function): JQueryXHR;
        public show(): Wizard;
        public dismiss(): Wizard;
      }
    }
  }
}

declare namespace TBE_EDITOR {
  export let fieldChanged: Function;
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

declare module 'TYPO3/CMS/Backend/Wizard' {
  export = new TYPO3.CMS.Backend.Wizard();
}

// type definition for global namespace object
interface Window {
  TYPO3: any;
  $: any;
  startInModule: Array<string>;
  inline: {
    delayedImportElement: (objectId: number, table: string, uid: number, type: string) => void
  };
  rawurlencode: Function;
  list_frame: Window;
  jump: Function;
  currentSubScript: string;
  currentModuleLoaded: string;
  fsMod: { [key: string]: any };
  nextLoadModuleUrl: string;
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

interface JQueryTypedEvent<T extends Event> extends JQueryEventObject {
  originalEvent: T;
}

/**
 * Required to make jQuery plugins "available" in TypeScript
 */
interface JQuery {
  clearable(options?: any): JQuery;

  datetimepicker(options?: any): JQuery;

  dragUploader(options?: any): JQuery;

  // To be able to use twbs/bootstrap-slider we have to override the definition of jquerui
  slider(options: { [key: string]: any }): any;

  // To be able to use jquery/autocomplete-slider we have to override the definition of jquerui
  autocomplete(options?: { [key: string]: any }): any;
}

