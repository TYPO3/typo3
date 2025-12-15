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
import{ScaffoldIdentifierEnum as m}from"@typo3/backend/enum/viewport/scaffold-identifier.js";import d from"@typo3/core/document-service.js";import u from"@typo3/core/event/regular-event.js";import{ModuleUtility as l,ModuleSelector as a}from"@typo3/backend/module.js";import{selector as c}from"@typo3/core/literals.js";import s from"@typo3/backend/module-menu.js";class n{static{this.toolbarSelector=".t3js-scaffold-toolbar"}constructor(){d.ready().then(()=>{this.initializeEvents()})}registerEvent(t){d.ready().then(()=>{t()}),new u("t3-topbar-update",t).bindTo(document.querySelector(m.header))}initializeEvents(){const t=document.querySelector(n.toolbarSelector);if(t===null)return;new u("click",(r,e)=>{r.preventDefault();const i=l.getRouteFromElement(e);s.App.showModule(i.identifier,i.params,r)}).delegateTo(t,a.link);const o=r=>{const e=r.detail.module;!e||!l.getFromName(e).link||this.highlightModule(e)};document.addEventListener("typo3-module-load",o),document.addEventListener("typo3-module-loaded",o)}highlightModule(t){const o=document.querySelector(n.toolbarSelector);if(o===null)return;o.querySelectorAll(a.link+".dropdown-item").forEach(e=>{e.classList.remove("active"),e.removeAttribute("aria-current")});const r=l.getFromName(t);this.highlightModuleItem(o,r,!0)}highlightModuleItem(t,o,r){const e=t.querySelectorAll(a.link+c`[data-moduleroute-identifier="${o.name}"].dropdown-item`);return e.forEach(i=>{i.classList.add("active"),r&&i.setAttribute("aria-current","location")}),e.length>0&&(r=!1),o.parent!==""&&this.highlightModuleItem(t,l.getFromName(o.parent),r),e.length>0}}export{n as default};
