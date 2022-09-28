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
export function getRecordFromName(n){const o=getParsedRecordFromName(n);return null===o?{name:n,component:"",navigationComponentId:"",parent:"",link:""}:{name:n,component:o.component||"",navigationComponentId:o.navigationComponentId||"",parent:o.parent||"",link:o.link||""}}let parsedInformation=null;function getParsedRecordFromName(n){if(null===parsedInformation){const n=String(document.querySelector(".t3js-scaffold-modulemenu")?.dataset.modulesInformation||"");if(""!==n)try{parsedInformation=JSON.parse(n)}catch(n){console.error("Invalid modules information provided."),parsedInformation=null}}return null!==parsedInformation&&n in parsedInformation?parsedInformation[n]:null}