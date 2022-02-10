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
import Notification from"@typo3/backend/notification.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";import Router from"@typo3/install/router.js";import{AbstractInlineModule}from"@typo3/install/module/abstract-inline-module.js";class DumpAutoload extends AbstractInlineModule{initialize(t){this.setButtonState(t,!1),new AjaxRequest(Router.getUrl("dumpAutoload")).get({cache:"no-cache"}).then(async t=>{const e=await t.resolve();!0===e.success&&Array.isArray(e.status)?e.status.length>0&&e.status.forEach(t=>{Notification.success(t.message)}):Notification.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},()=>{Notification.error("Autoloader not dumped","Dumping autoload files failed for unknown reasons. Check the system for broken extensions and try again.")}).finally(()=>{this.setButtonState(t,!0)})}}export default new DumpAutoload;