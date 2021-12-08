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
define(["require","exports"],(function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.ModuleStateStorage=void 0;class r{static update(t,e,i,n){if("number"==typeof e)e=e.toString(10);else if("string"!=typeof e)throw new SyntaxError("identifier must be of type string");if("number"==typeof n)n=n.toString(10);else if("string"!=typeof n&&null!=n)throw new SyntaxError("mount must be of type string");const s=r.assignProperties({mount:n,identifier:e,selected:i},r.fetch(t));r.commit(t,s)}static updateWithCurrentMount(t,e,i){r.update(t,e,i,r.current(t).mount)}static current(t){return r.fetch(t)||r.createCurrentState()}static purge(){Object.keys(sessionStorage).filter(t=>t.startsWith(r.prefix)).forEach(t=>sessionStorage.removeItem(t))}static fetch(t){const e=sessionStorage.getItem(r.prefix+t);return null===e?null:JSON.parse(e)}static commit(t,e){sessionStorage.setItem(r.prefix+t,JSON.stringify(e))}static assignProperties(t,e){let i=Object.assign(r.createCurrentState(),e);return t.mount&&(i.mount=t.mount),t.identifier&&(i.identifier=t.identifier),t.selected&&(i.selection=i.identifier),i}static createCurrentState(){return{mount:null,identifier:"",selection:null}}}e.ModuleStateStorage=r,r.prefix="t3-module-state-",window.ModuleStateStorage=r}));