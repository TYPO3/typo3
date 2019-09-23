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
define(["require","exports","jquery","../../Router","TYPO3/CMS/Backend/Notification"],function(e,s,r,a,t){"use strict";return new class{initialize(e){r.ajax({url:a.getUrl("resetBackendUserUc"),cache:!1,beforeSend:()=>{e.addClass("disabled").prop("disabled",!0)},success:e=>{!0===e.success&&Array.isArray(e.status)?e.status.length>0&&e.status.forEach(e=>{t.success(e.message)}):t.error("Something went wrong ...")},error:()=>{t.error("Resetting backend user uc failed. Please check the system for missing database fields and try again.")},complete:()=>{e.removeClass("disabled").prop("disabled",!1)}})}}});