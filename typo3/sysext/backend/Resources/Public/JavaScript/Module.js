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
define(["require","exports"],(function(e,n){"use strict";Object.defineProperty(n,"__esModule",{value:!0}),n.getRecordFromName=void 0,n.getRecordFromName=function(e){const n=document.getElementById(e);return n?{name:e,component:n.dataset.component,navigationComponentId:n.dataset.navigationcomponentid,link:n.getAttribute("href")}:{name:e,component:"",navigationComponentId:"",link:""}}}));