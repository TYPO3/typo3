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
define(["require","exports","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Router"],(function(e,s,a,r,t){"use strict";return new class{initialize(e){e.addClass("disabled").prop("disabled",!0),new r(t.getUrl("cacheClearAll","maintenance")).get({cache:"no-cache"}).then(async e=>{const s=await e.resolve();!0===s.success&&Array.isArray(s.status)?s.status.length>0&&s.status.forEach(e=>{a.success(e.title,e.message)}):a.error("Something went wrong clearing caches")},()=>{a.error("Clearing caches went wrong on the server side. Check the system for broken extensions or missing database tables and try again")}).finally(()=>{e.removeClass("disabled").prop("disabled",!1)})}}}));