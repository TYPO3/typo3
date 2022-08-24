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
import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import{AbstractInlineModule}from"@typo3/install/module/abstract-inline-module.js";import Notification from"@typo3/backend/notification.js";import Router from"@typo3/install/router.js";class ResetBackendUserUc extends AbstractInlineModule{initialize(e){this.setButtonState(e,!1),new AjaxRequest(Router.getUrl("resetBackendUserUc")).get({cache:"no-cache"}).then((async e=>{const t=await e.resolve();!0===t.success&&Array.isArray(t.status)?t.status.length>0&&t.status.forEach((e=>{Notification.success(e.title,e.message)})):Notification.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")}),(()=>{Notification.error("Reset preferences of all backend users failed","Resetting preferences of all backend users failed for an unknown reason. Please check your server's logs for further investigation.")})).finally((()=>{this.setButtonState(e,!0)}))}}export default new ResetBackendUserUc;