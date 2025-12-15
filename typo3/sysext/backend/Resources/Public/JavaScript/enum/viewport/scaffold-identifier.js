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
import{ContentNavigationSlotEnum as n}from"@typo3/backend/viewport/content-navigation.js";var e;(function(t){t.scaffold=".t3js-scaffold",t.header=".t3js-scaffold-header",t.sidebar=".t3js-scaffold-sidebar",t.content=".t3js-scaffold-content",t.contentModuleRouter="typo3-backend-module-router",t.contentModuleIframe=".t3js-scaffold-content-module-iframe"})(e||(e={}));class a{static{this.selector='typo3-backend-content-navigation[identifier="backend"]'}static getContentNavigation(){return document.querySelector(this.selector)}static getNavigationContainer(){return this.getContentNavigation()?.querySelector(`[slot="${n.navigation}"]`)??null}static getContentContainer(){return this.getContentNavigation()?.querySelector(`[slot="${n.content}"]`)??null}}export{a as ScaffoldContentArea,e as ScaffoldIdentifierEnum};
