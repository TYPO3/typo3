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
export class ModuleStateStorage{static update(t,e,r,o){if("number"==typeof e)e=e.toString(10);else if("string"!=typeof e)throw new SyntaxError("identifier must be of type string");if("number"==typeof o)o=o.toString(10);else if("string"!=typeof o&&null!=o)throw new SyntaxError("mount must be of type string");const i=ModuleStateStorage.assignProperties({mount:o,identifier:e,selected:r},ModuleStateStorage.fetch(t));ModuleStateStorage.commit(t,i)}static updateWithCurrentMount(t,e,r){ModuleStateStorage.update(t,e,r,ModuleStateStorage.current(t).mount)}static current(t){return ModuleStateStorage.fetch(t)||ModuleStateStorage.createCurrentState()}static purge(){Object.keys(sessionStorage).filter((t=>t.startsWith(ModuleStateStorage.prefix))).forEach((t=>sessionStorage.removeItem(t)))}static fetch(t){const e=sessionStorage.getItem(ModuleStateStorage.prefix+t);return null===e?null:JSON.parse(e)}static commit(t,e){sessionStorage.setItem(ModuleStateStorage.prefix+t,JSON.stringify(e))}static assignProperties(t,e){const r=Object.assign(ModuleStateStorage.createCurrentState(),e);return t.mount&&(r.mount=t.mount),t.identifier&&(r.identifier=t.identifier),t.selected&&(r.selection=r.identifier),r}static createCurrentState(){return{mount:null,identifier:"",selection:null}}}ModuleStateStorage.prefix="t3-module-state-",window.ModuleStateStorage=ModuleStateStorage;