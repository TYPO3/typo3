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
 * Module: @typo3/backend/module
 */

export interface ModuleState {
  url: string;
  title?: string | null;
  module?: string;
}

/**
 * @internal
 */
export interface Module {
  name: string;
  aliases: Array<string>;
  component: string;
  navigationComponentId: string;
  parent: string;
  link: string;
}

/**
 * @internal
 */
export enum ModuleSelector {
  link = '[data-moduleroute-identifier]'
}

/**
 * @internal
 */
export interface ModuleRoute {
  identifier: string;
  params: string | null;
}

/**
 * @internal
 */
export class ModuleUtility {
  public static getRouteFromElement(element: HTMLElement): ModuleRoute {
    const moduleRoute: ModuleRoute = {
      identifier: element.dataset.modulerouteIdentifier,
      params: element.dataset.modulerouteParams
    };

    return moduleRoute;
  }

  /**
   * Gets the module properties from module information data attribute
   */
  public static getFromName(name: string): Module {
    const parsedRecord = getParsedRecordFromName(name);
    if (parsedRecord === null) {
      return {
        name: name,
        aliases: [],
        component: '',
        navigationComponentId: '',
        parent: '',
        link: ''
      };
    }
    return {
      name: name,
      aliases: parsedRecord.aliases || [],
      component: parsedRecord.component || '',
      navigationComponentId: parsedRecord.navigationComponentId || '',
      parent: parsedRecord.parent || '',
      link: parsedRecord.link || '',
    };
  }
}

interface ParsedInformation {
  [index: string]: Partial<Module>;
}

/**
 * Runtime cache of json serialized module information
 */
let parsedInformation: ParsedInformation = null;

/**
 * Flushes the runtime cache containing parsed module information
 *
 * @internal
 */
export function flushModuleCache(): void {
  parsedInformation = null;
}

function getParsedInformation(): ParsedInformation|null {
  if (parsedInformation === null) {
    const modulesInformation: string = String((document.querySelector('[data-modulemenu]') as HTMLElement)?.dataset.modulesInformation || '');
    if (modulesInformation !== '') {
      try {
        parsedInformation = JSON.parse(modulesInformation);
      } catch {
        console.error('Invalid modules information provided.');
        parsedInformation = null;
      }
    }
  }
  return parsedInformation;
}

function getParsedRecordFromName(name: string): Partial<Module>|null {
  const parsedModuleInformation: ParsedInformation = getParsedInformation();
  if (parsedModuleInformation !== null) {
    for (const [key, module] of Object.entries(parsedModuleInformation)) {
      if (name === key || module.aliases.includes(name)) {
        return parsedModuleInformation[key];
      }
    }
  }
  return null;
}
