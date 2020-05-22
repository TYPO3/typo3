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
define(["require","exports","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Router","../AbstractInlineModule"],(function(e,t,s,a,n,r){"use strict";class i extends r.AbstractInlineModule{initialize(e){this.setButtonState(e,!1),new a(n.getUrl("cacheClearAll","maintenance")).get({cache:"no-cache"}).then(async e=>{const t=await e.resolve();!0===t.success&&Array.isArray(t.status)?t.status.length>0&&t.status.forEach(e=>{s.success(e.title,e.message)}):s.error("Something went wrong clearing caches")},()=>{s.error("Clearing caches failed","Clearing caches went wrong on the server side. Check the system for broken extensions or missing database tables and try again.")}).finally(()=>{this.setButtonState(e,!0)})}}return new i}));