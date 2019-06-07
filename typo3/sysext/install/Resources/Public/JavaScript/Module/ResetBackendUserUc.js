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
define(["require","exports","jquery","../Router","TYPO3/CMS/Backend/Notification"],function(e,s,t,r,n){"use strict";return new(function(){function e(){}return e.prototype.initialize=function(e){t.ajax({url:r.getUrl("resetBackendUserUc"),cache:!1,beforeSend:function(){e.addClass("disabled").prop("disabled",!0)},success:function(e){!0===e.success&&Array.isArray(e.status)?e.status.length>0&&e.status.forEach(function(e){n.success(e.message)}):n.error("Something went wrong ...")},error:function(e){n.error("Resetting backend user uc failed. Please check the system for missing database fields and try again.")},complete:function(){e.removeClass("disabled").prop("disabled",!1)}})},e}())});