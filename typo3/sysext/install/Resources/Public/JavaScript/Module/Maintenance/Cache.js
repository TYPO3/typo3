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
define(["require","exports","jquery","../../Router","TYPO3/CMS/Backend/Notification"],function(e,s,r,a,t){"use strict";return new class{initialize(e){r.ajax({url:a.getUrl("cacheClearAll","maintenance"),cache:!1,beforeSend:()=>{e.addClass("disabled").prop("disabled",!0)},success:e=>{!0===e.success&&Array.isArray(e.status)?e.status.length>0&&e.status.forEach(e=>{t.success(e.title,e.message)}):t.error("Something went wrong clearing caches")},error:()=>{t.error("Clearing caches went wrong on the server side. Check the system for broken extensions or missing database tables and try again")},complete:()=>{e.removeClass("disabled").prop("disabled",!1)}})}}});