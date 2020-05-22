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
define(["require","exports","TYPO3/CMS/Core/Ajax/AjaxRequest","../AbstractInlineModule","TYPO3/CMS/Backend/Notification","../../Router"],(function(e,s,t,r,n,a){"use strict";class o extends r.AbstractInlineModule{initialize(e){this.setButtonState(e,!1),new t(a.getUrl("resetBackendUserUc")).get({cache:"no-cache"}).then(async e=>{const s=await e.resolve();!0===s.success&&Array.isArray(s.status)?s.status.length>0&&s.status.forEach(e=>{n.success(e.title,e.message)}):n.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},()=>{n.error("Reset preferences of all backend users failed","Resetting preferences of all backend users failed for an unknown reason. Please check your server's logs for further investigation.")}).finally(()=>{this.setButtonState(e,!0)})}}return new o}));