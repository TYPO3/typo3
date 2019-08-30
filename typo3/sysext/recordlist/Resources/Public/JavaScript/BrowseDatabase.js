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
define(["require","exports","jquery","./ElementBrowser"],function(e,t,r,n){"use strict";return new class{constructor(){r(()=>{r("[data-close]").on("click",e=>{e.preventDefault();const t=r(e.currentTarget).parents("span").data();n.insertElement(t.table,t.uid,"db",t.title,"","",t.icon,"",1===parseInt(r(e.currentTarget).data("close"),10))})});const e=document.getElementById("db_list-searchbox-toolbar");e.style.display="block",e.style.position="relative"}}});