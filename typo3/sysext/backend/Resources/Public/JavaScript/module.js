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
var r;(function(o){o.link="[data-moduleroute-identifier]"})(r||(r={}));class i{static getRouteFromElement(e){return{identifier:e.dataset.modulerouteIdentifier,params:e.dataset.modulerouteParams}}static getFromName(e){const n=d(e);return n===null?{name:e,aliases:[],component:"",navigationComponentId:"",parent:"",link:""}:{name:e,aliases:n.aliases||[],component:n.component||"",navigationComponentId:n.navigationComponentId||"",parent:n.parent||"",link:n.link||""}}}let t=null;function l(){t=null}function u(){if(t===null){const o=String(document.querySelector("[data-modulemenu]")?.dataset.modulesInformation||"");if(o!=="")try{t=JSON.parse(o)}catch{console.error("Invalid modules information provided."),t=null}}return t}function d(o){const e=u();if(e!==null){for(const[n,a]of Object.entries(e))if(o===n||a.aliases.includes(o))return e[n]}return null}export{r as ModuleSelector,i as ModuleUtility,l as flushModuleCache};
