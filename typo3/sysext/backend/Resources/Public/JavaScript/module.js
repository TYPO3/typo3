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
export var ModuleSelector;!function(e){e.link="[data-moduleroute-identifier]"}(ModuleSelector||(ModuleSelector={}));export class ModuleUtility{static getRouteFromElement(e){return{identifier:e.dataset.modulerouteIdentifier,params:e.dataset.modulerouteParams}}static getFromName(e){const n=getParsedRecordFromName(e);return null===n?{name:e,component:"",navigationComponentId:"",parent:"",link:""}:{name:e,component:n.component||"",navigationComponentId:n.navigationComponentId||"",parent:n.parent||"",link:n.link||""}}}let parsedInformation=null;export function flushModuleCache(){parsedInformation=null}function getParsedInformation(){if(null===parsedInformation){const e=String(document.querySelector("[data-modulemenu]")?.dataset.modulesInformation||"");if(""!==e)try{parsedInformation=JSON.parse(e)}catch(e){console.error("Invalid modules information provided."),parsedInformation=null}}return parsedInformation}function getParsedRecordFromName(e){const n=getParsedInformation();return null!==n&&e in n?n[e]:null}