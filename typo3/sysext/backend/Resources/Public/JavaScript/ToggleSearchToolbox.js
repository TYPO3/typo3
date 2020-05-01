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
define(["require","exports","jquery","./DocumentHeader","TYPO3/CMS/Backend/Input/Clearable"],(function(e,t,i,l){"use strict";return new class{constructor(){i(()=>{this.initialize()})}initialize(){const e=i("#db_list-searchbox-toolbar");let t;if(i(".t3js-toggle-search-toolbox").on("click",()=>{e.toggle(),l.reposition(),e.is(":visible")&&i("#search_field").focus()}),null!==(t=document.getElementById("search_field"))){const e=""!==t.value;t.clearable({onClear:t=>{e&&t.closest("form").submit()}})}}}}));