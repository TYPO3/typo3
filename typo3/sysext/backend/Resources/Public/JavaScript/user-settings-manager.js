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
import{BroadcastMessage}from"@typo3/backend/broadcast-message.js";import BroadcastService from"@typo3/backend/broadcast-service.js";var Identifier;!function(e){e.colorSchemeSwitch="typo3-backend-color-scheme-switch"}(Identifier||(Identifier={}));class UserSettingsManager{constructor(){document.addEventListener("typo3:color-scheme:update",(e=>this.onColorSchemeUpdate(e.detail))),document.addEventListener("typo3:theme:update",(e=>this.onThemeUpdate(e.detail))),document.addEventListener("typo3:color-scheme:broadcast",(e=>this.activateColorScheme(e.detail.payload.colorScheme))),document.addEventListener("typo3:theme:broadcast",(e=>this.activateTheme(e.detail.payload.theme)))}onColorSchemeUpdate(e){const{colorScheme:t}=e;this.activateColorScheme(t),BroadcastService.post(new BroadcastMessage("color-scheme","broadcast",{colorScheme:t}))}onThemeUpdate(e){const{theme:t}=e;this.activateTheme(t),BroadcastService.post(new BroadcastMessage("theme","broadcast",{theme:t}))}activateColorScheme(e){document.documentElement.setAttribute("data-color-scheme",e),window.frames.list_frame?.document.documentElement.setAttribute("data-color-scheme",e);const t=document.querySelector(Identifier.colorSchemeSwitch);t&&(t.activeColorScheme=e)}activateTheme(e){document.documentElement.setAttribute("data-theme",e),window.frames.list_frame?.document.documentElement.setAttribute("data-theme",e)}}export default new UserSettingsManager;