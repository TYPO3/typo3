/**
 * Currently a mixture between namespace and global object
 * Add types as you use them
 */
declare namespace TYPO3 {
  export let Backend: typeof import('@typo3/backend/viewport').default;
  export let ExtensionManager: typeof import('@typo3/extensionmanager/main').default;
  export let FORMEDITOR_APP: import('@typo3/form/backend/form-editor').FormEditor;
  export let FORMMANAGER_APP: import('@typo3/form/backend/form-manager').FormManager;
  export let FormEngine: typeof import('@typo3/backend/form-engine').default;
  export let HotkeyStorage: typeof import('@typo3/backend/hotkeys/hotkey-storage').default;
  export let Hotkeys: typeof import('@typo3/backend/hotkeys').default;
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
  export let WorkspacesMenu: typeof import('@typo3/workspaces/toolbar/workspaces-menu').default;
  export const lang: {
    [key: string]: string
  };
  export const configuration: {
    showRefreshLoginPopup: boolean,
    username: string,
  };
  export namespace settings {
    export const ajaxUrls: {
      [key: string]: string
    };
    export const cssUrls: {
      [key: string]: string
    };
    export namespace Clipboard {
      export const moduleUrl: string;
    }
    export namespace FileCommit {
      export const moduleUrl: string;
    }
    export namespace FormEditor {
      export const typo3WinBrowserUrl: string;
    }
    export namespace FormEngine {
      export const moduleUrl: string;
      export const formName: string;

      export const doSaveFieldName: string;
    }
    export namespace FormEngineInline {
      export const config: {
        [key: string]: {
          min: number,
          max: number,
          sortable: boolean,
          top: {
            table: string,
            uid: number,
          }
          context: import('@typo3/backend/form-engine/inline-relation/ajax-dispatcher').Context,
        }
      }
      export const unique: {
        // todo: Resolve typing (possibly being real) issues in @typo3/backend/form-engine/container/inline-control-container
        // and use `import('@typo3/backend/form-engine/container/inline-control-container').UniqueDefinition`
        [key: string]: any,
      }
    }
    export namespace WebLayout {
      export const moduleUrl: string;
    }
    export namespace RecordCommit {
      export const moduleUrl: string;
    }
    export namespace RecordHistory {
      export const moduleUrl: string;
    }
    export namespace Recycler {
      export const deleteDisable: boolean;
      export const depthSelection: number;
      export const pagingSize: string;
      export const startUid: number;
      export const tableSelection: string;
    }
    export namespace ShowItem {
      export const moduleUrl: string;
    }
    export namespace Workspaces {
      export const id: string;
      export const token: string;
    }
  }
}

declare namespace TBE_EDITOR {
  export const customEvalFunctions: { [key: string]: (value: string) => string };
}

// type definition for global namespace object
interface Window {
  TYPO3: Partial<typeof TYPO3>;
  ModuleStateStorage: typeof import('@typo3/backend/storage/module-state-storage').ModuleStateStorage;
  list_frame: Window;
}
