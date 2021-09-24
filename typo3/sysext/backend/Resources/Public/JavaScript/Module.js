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
define(["require","exports"],(function(t,e){"use strict";Object.defineProperty(e,"__esModule",{value:!0}),e.getRecordFromName=void 0,e.getRecordFromName=function(t){const e=document.getElementById(t);return e?{name:t,component:e.dataset.component,navigationComponentId:e.dataset.navigationcomponentid,navigationFrameScript:e.dataset.navigationframescript,navigationFrameScriptParam:e.dataset.navigationframescriptparameters,link:e.getAttribute("href")}:{name:t,component:"",navigationComponentId:"",navigationFrameScript:"",navigationFrameScriptParam:"",link:""}}}));