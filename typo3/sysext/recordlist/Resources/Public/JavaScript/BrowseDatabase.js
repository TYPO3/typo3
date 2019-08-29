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
define(["require","exports","jquery","./ElementBrowser"],function(e,t,n,r){"use strict";return new(function(){return function(){n(function(){n("[data-close]").on("click",function(e){e.preventDefault();var t=n(e.currentTarget).parents("span").data();r.insertElement(t.table,t.uid,"db",t.title,"","",t.icon,"",1===parseInt(n(e.currentTarget).data("close"),10))})});var e=document.getElementById("db_list-searchbox-toolbar");e.style.display="block",e.style.position="relative"}}())});