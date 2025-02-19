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
function o(t){const e=new CustomEvent("typo3:import-javascript-module",{detail:{specifier:t,importPromise:null}});return top.document.dispatchEvent(e),e.detail.importPromise?e.detail.importPromise:Promise.reject(new Error("Top level did not respond with a promise."))}export{o as topLevelModuleImport};
