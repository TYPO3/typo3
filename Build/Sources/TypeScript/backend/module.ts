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
  title?: string|null;
  module?: string;
}

/**
 * @internal
 */
export interface Module {
  name: string;
  component: string;
  navigationComponentId: string;
  link: string;
}


/**
 * Gets the module properties from module menu markup (data attributes)
 *
 * @param {string} name
 * @returns {Module}
 * @internal
 */
export function getRecordFromName(name: string): Module {
  const moduleElement = document.getElementById(name);
  if (!moduleElement) {
    return {
      name: name,
      component: '',
      navigationComponentId: '',
      link: ''
    };
  }
  return {
    name: name,
    component: moduleElement.dataset.component,
    navigationComponentId: moduleElement.dataset.navigationcomponentid,
    link: moduleElement.getAttribute('href')
  };
}
