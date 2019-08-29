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
define(["require","exports","jquery","./ElementBrowser","TYPO3/CMS/Backend/Modal","TYPO3/CMS/Backend/Severity"],function(e,r,n,t,o,a){"use strict";return new(function(){return function(){n(function(){n("[data-folder-id]").on("click",function(e){e.preventDefault();var r=n(e.currentTarget),o=r.data("folderId"),a=1===parseInt(r.data("close"),10);t.insertElement("",o,"folder",o,o,"","","",a)}),n(".t3js-folderIdError").on("click",function(e){e.preventDefault(),o.confirm("",n(e.currentTarget).data("message"),a.error,[],[])})})}}())});