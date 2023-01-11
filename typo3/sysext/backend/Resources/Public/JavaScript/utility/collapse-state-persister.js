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
import{Collapse as BootstrapCollapse}from"bootstrap";import Client from"@typo3/backend/storage/client.js";import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";export class CollapseStatePersister{constructor(){this.localStorageKey="collapse-states",DocumentService.ready().then((()=>{this.registerEventListener(),this.recoverStates()}))}registerEventListener(){const e='.collapse[data-persist-collapse-state="true"]';new RegularEvent("show.bs.collapse",(e=>{this.toStorage(e.target.id,!0)})).delegateTo(document,e),new RegularEvent("hide.bs.collapse",(e=>{this.toStorage(e.target.id,!1)})).delegateTo(document,e)}recoverStates(){const e=this.fromStorage();for(const[t,o]of Object.entries(e)){const e=document.getElementById(t);if(null===e)continue;const r=BootstrapCollapse.getOrCreateInstance(e,{toggle:!1});o?r.show():r.hide()}}fromStorage(){const e=Client.get(this.localStorageKey);return null===e?{}:JSON.parse(e)}toStorage(e,t){const o=this.fromStorage();o[e]=t,Client.set(this.localStorageKey,JSON.stringify(o))}}export default new CollapseStatePersister;