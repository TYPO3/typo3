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
define(["require","exports","jquery","../Router","TYPO3/CMS/Backend/Notification"],function(t,e,r,o,n){"use strict";return new(function(){function t(){this.selectorModalBody=".t3js-modal-body",this.currentModal={}}return t.prototype.initialize=function(t){this.currentModal=t,this.getData()},t.prototype.getData=function(){var t=this.currentModal.find(this.selectorModalBody);r.ajax({url:o.getUrl("systemInformationGetData"),cache:!1,success:function(e){!0===e.success?t.empty().append(e.html):n.error("Something went wrong")},error:function(e){o.handleAjaxError(e,t)}})},t}())});