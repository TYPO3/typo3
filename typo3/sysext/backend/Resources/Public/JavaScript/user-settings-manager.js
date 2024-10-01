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
import{BroadcastMessage}from"@typo3/backend/broadcast-message.js";import BroadcastService from"@typo3/backend/broadcast-service.js";var Identifier;!function(e){e.colorSchemeSwitch="typo3-backend-color-scheme-switch"}(Identifier||(Identifier={}));class UserSettingsManager{constructor(){document.addEventListener("typo3:color-scheme:update",(e=>this.onColorSchemeUpdate(e.detail))),document.addEventListener("typo3:theme:update",(e=>this.onThemeUpdate(e.detail))),document.addEventListener("typo3:color-scheme:broadcast",(e=>this.activateColorScheme(e.detail.payload.colorScheme))),document.addEventListener("typo3:theme:broadcast",(e=>this.activateTheme(e.detail.payload.theme)))}onColorSchemeUpdate(e){const{colorScheme:t}=e;this.activateColorScheme(t),BroadcastService.post(new BroadcastMessage("color-scheme","broadcast",{colorScheme:t}))}onThemeUpdate(e){const{theme:t}=e;this.activateTheme(t),BroadcastService.post(new BroadcastMessage("theme","broadcast",{theme:t}))}activateColorScheme(e){const t=document.querySelector(Identifier.colorSchemeSwitch);t&&(t.activeColorScheme=e),this.setStyleChangingDocumentAttribute("data-color-scheme",e)}activateTheme(e){this.setStyleChangingDocumentAttribute("data-theme",e)}async setStyleChangingDocumentAttribute(e,t){const a=document.documentElement,o=window.frames.list_frame?.document.documentElement,s=()=>{a.classList.add("t3js-disable-transitions"),o?.classList.add("t3js-disable-transitions"),a.setAttribute(e,t),o?.setAttribute(e,t)},i=()=>{a.classList.remove("t3js-disable-transitions"),o?.classList.remove("t3js-disable-transitions")};if(window.matchMedia("(prefers-reduced-motion: reduce)").matches||!("startViewTransition"in document)||"function"!=typeof document.startViewTransition)return s(),await new Promise((e=>requestAnimationFrame(e))),o&&await new Promise((e=>window.frames.list_frame.requestAnimationFrame(e))),void i();await document.startViewTransition(s).finished,i()}}export default new UserSettingsManager;