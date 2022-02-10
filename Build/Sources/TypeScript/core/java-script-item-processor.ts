/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * This processor is used as client-side counterpart of `\TYPO3\CMS\Core\Page\JavaScriptItems
 *
 * @module @typo3/core/java-script-item-processor
 * @internal Use in TYPO3 core only, API can change at any time!
 */

const FLAG_USE_REQUIRE_JS = 1;
const FLAG_USE_IMPORTMAP = 2;
const FLAG_USE_TOP_WINDOW = 16;
const deniedProperties = ['__proto__', 'prototype', 'constructor'];
const allowedJavaScriptItemTypes = ['assign', 'invoke', 'instance'];

export interface JavaScriptInstruction {
  type: string;
  assignments?: object;
  method?: string;
  args: Array<any>;
}

export interface JavaScriptItemPayload {
  name: string;
  flags: number;
  exportName?: string;
  items: JavaScriptInstruction[];
}

export interface JavaScriptItem {
  type: string;
  payload: JavaScriptItemPayload;
}

/**
 * Fallback to importShim() once import() failed the first time
 * (considering importmaps are not supported by the browser).
 */
let useShim = false;

const moduleImporter = (moduleName: string): Promise<any> => {
  if (useShim) {
    return (window as any).importShim(moduleName)
  } else {
    return import(moduleName).catch(() => {
      // Consider that importmaps are not supported and use shim from now on
      useShim = true;
      return moduleImporter(moduleName)
    })
  }
};

export function loadModule(payload: JavaScriptItemPayload): Promise<any> {
  if (!payload.name) {
    throw new Error('JavaScript module name is required');
  }

  if ((payload.flags & FLAG_USE_IMPORTMAP) === FLAG_USE_IMPORTMAP) {
    if (!(payload.flags & FLAG_USE_TOP_WINDOW)) {
      return moduleImporter(payload.name);
    } else {
      const event = new CustomEvent<{ specifier: string; importPromise: null|Promise<any>; }>(
        'typo3:import-javascript-module',
        {
          detail: {
            specifier: payload.name,
            importPromise: null
          }
        }
      );
      top.document.dispatchEvent(event);
      return event.detail.importPromise || Promise.reject(new Error('Top-level import failed'));
    }
  }

  if ((payload.flags & FLAG_USE_REQUIRE_JS) === FLAG_USE_REQUIRE_JS) {
    return new Promise((resolve, reject) => {
      const windowRef = (payload.flags & FLAG_USE_TOP_WINDOW) === FLAG_USE_TOP_WINDOW ? top.window : window;
      windowRef.require(
        [payload.name],
        (module: any) => resolve(module),
        (e: any) => reject(e)
      );
    });
  }

  throw new Error('Unknown JavaScript module type')
}

function executeJavaScriptModuleInstruction(json: JavaScriptItemPayload) {
  // `name` is required
  if (!json.name) {
    throw new Error('JavaScript module name is required');
  }
  if (!json.items) {
    loadModule(json);
    return;
  }
  const exportName = json.exportName;
  const resolveSubjectRef = (__esModule: any): any => {
    if (typeof exportName === 'string') {
      return __esModule[exportName];
    }
    if ((json.flags & FLAG_USE_REQUIRE_JS) === FLAG_USE_REQUIRE_JS) {
      return __esModule;
    }
    return __esModule.default;
  }
  const items = json.items
    .filter((item) => allowedJavaScriptItemTypes.includes(item.type))
    .map((item) => {
      if (item.type === 'assign') {
        return (__esModule: any) => {
          const subjectRef = resolveSubjectRef(__esModule);
          mergeRecursive(subjectRef, item.assignments);
        };
      } else if (item.type === 'invoke') {
        return (__esModule: any) => {
          const subjectRef = resolveSubjectRef(__esModule);
          subjectRef[item.method].apply(subjectRef, item.args);
        };
      } else if (item.type === 'instance') {
        return (__esModule: any) => {
          // this `null` is `thisArg` scope of `Function.bind`,
          // which will be reset when invoking `new`
          const args = [null].concat(item.args);
          const subjectRef = resolveSubjectRef(__esModule);
          new (subjectRef.bind.apply(subjectRef, args));
        }
      } else {
        return (__esModule: any) => {
          return;
        }
      }
    });

  loadModule(json).then(
    (subjectRef) => items.forEach((item) => item.call(null, subjectRef))
  );
}

function isObjectInstance(item: any) {
  return item instanceof Object && !(item instanceof Array);
}

function mergeRecursive(target: { [key: string]: any }, source: { [key: string]: any }) {
  Object.keys(source).forEach((property) => {
    if (deniedProperties.indexOf(property) !== -1) {
      throw new Error('Property ' + property + ' is not allowed');
    }
    if (!isObjectInstance(source[property]) || typeof target[property] === 'undefined') {
      Object.assign(target, {[property]:source[property]});
    } else {
      mergeRecursive(target[property], source[property]);
    }
  });
}

export class JavaScriptItemProcessor {
  private invokableNames: string[] = ['globalAssignment', 'javaScriptModuleInstruction'];

  /**
   * Processes multiple items and delegates to handlers
   * (globalAssignment, javaScriptModuleInstruction)
   */
  public processItems(items: JavaScriptItem[]) {
    items.forEach((item) => this.invoke(item.type, item.payload));
  }

  private invoke(name: string, data: any) {
    if (!this.invokableNames.includes(name) || typeof (this as any)[name] !== 'function') {
      throw new Error('Unknown handler name "' + name + '"');
    }
    (this as any)[name].call(this, data);
  }

  /**
   * Assigns (filtered) variables to `window` object globally.
   */
  private globalAssignment(payload: { [key: string]: any }) {
    mergeRecursive(window, payload);
  }

  /**
   * Loads and invokes a JavaScript (ESM) or requires.js (AMD) module.
   */
  private javaScriptModuleInstruction(payload: JavaScriptItemPayload) {
    executeJavaScriptModuleInstruction(payload);
  }
}
