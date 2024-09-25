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
export class ModuleStateStorage{static update(t,e){if("number"==typeof e)e=e.toString(10);else if("string"!=typeof e)throw new SyntaxError("identifier must be of type string");const r=ModuleStateStorage.current(t),i={identifier:e,treeIdentifier:e===r.identifier?r.treeIdentifier:null};return ModuleStateStorage.commit(t,"update",i),i}static updateWithTreeIdentifier(t,e,r){if("number"==typeof e)e=e.toString(10);else if("string"!=typeof e)throw new SyntaxError("identifier must be of type string");if("number"==typeof r)r=r.toString(10);else if("string"!=typeof r)throw new SyntaxError("treeIdentifier must be of type string");const i={identifier:e,treeIdentifier:r};return ModuleStateStorage.commit(t,"update-with-tree-identifier",i),i}static updateWithCurrentMount(t,e){ModuleStateStorage.update(t,e)}static current(t){return{...ModuleStateStorage.getInitialState(),...ModuleStateStorage.fetch(t)??{}}}static purge(){Object.keys(sessionStorage).filter((t=>t.startsWith(ModuleStateStorage.prefix))).forEach((t=>sessionStorage.removeItem(t)))}static fetch(t){const e=sessionStorage.getItem(ModuleStateStorage.prefix+t);return null===e?null:JSON.parse(e)}static async commit(t,e,r){const i=ModuleStateStorage.current(t);sessionStorage.setItem(ModuleStateStorage.prefix+t,JSON.stringify(r)),top.document.dispatchEvent(new CustomEvent("typo3:module-state-storage:"+e+":"+t,{detail:{state:r,oldState:i}}))}static getInitialState(){return{identifier:"",treeIdentifier:null}}}ModuleStateStorage.prefix="t3-module-state-",window.ModuleStateStorage=ModuleStateStorage;