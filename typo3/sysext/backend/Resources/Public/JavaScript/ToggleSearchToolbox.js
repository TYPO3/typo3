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
define(["require","exports","jquery","./DocumentHeader","TYPO3/CMS/Backend/jquery.clearable"],function(e,i,s,t){"use strict";return new class{constructor(){s(()=>{this.initialize()})}initialize(){const e=s("#db_list-searchbox-toolbar");s(".t3js-toggle-search-toolbox").on("click",()=>{e.toggle(),t.reposition(),e.is(":visible")&&s("#search_field").focus()});const i=s("#search_field"),o=""!==i.val();i.clearable({onClear:()=>{o&&s(this).closest("form").submit()}})}}});