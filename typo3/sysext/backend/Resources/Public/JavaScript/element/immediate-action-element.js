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
import n from"@typo3/backend/utility.js";import{EventDispatcher as i}from"@typo3/backend/event/event-dispatcher.js";class r extends HTMLElement{constructor(){super(...arguments),this.args=[]}static get observedAttributes(){return["action","args","args-list"]}static async getDelegate(t){switch(t){case"TYPO3.ModuleMenu.App.refreshMenu":const{default:s}=await import("@typo3/backend/module-menu.js");return s.App.refreshMenu.bind(s.App);case"TYPO3.Backend.Topbar.refresh":const{default:e}=await import("@typo3/backend/viewport.js");return e.Topbar.refresh.bind(e.Topbar);case"TYPO3.WindowManager.localOpen":const{default:a}=await import("@typo3/backend/window-manager.js");return a.localOpen.bind(a);case"TYPO3.Backend.Storage.ModuleStateStorage.update":return(await import("@typo3/backend/storage/module-state-storage.js")).ModuleStateStorage.update;case"TYPO3.Backend.Storage.ModuleStateStorage.updateWithCurrentMount":return(await import("@typo3/backend/storage/module-state-storage.js")).ModuleStateStorage.updateWithCurrentMount;case"TYPO3.Backend.Event.EventDispatcher.dispatchCustomEvent":return i.dispatchCustomEvent;default:throw Error('Unknown action "'+t+'"')}}attributeChangedCallback(t,s,e){if(t==="action")this.action=e;else if(t==="args"){const a=e.replace(/&quot;/g,'"'),o=JSON.parse(a);this.args=o instanceof Array?n.trimItems(o):[]}else if(t==="args-list"){const a=e.split(",");this.args=n.trimItems(a)}}connectedCallback(){if(!this.action)throw new Error("Missing mandatory action attribute");r.getDelegate(this.action).then(t=>t(...this.args))}}window.customElements.define("typo3-immediate-action",r);export{r as ImmediateActionElement};
