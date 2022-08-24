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
import RegularEvent from"@typo3/core/event/regular-event.js";import DocumentService from"@typo3/core/document-service.js";class FileReplace{constructor(){DocumentService.ready().then((()=>{this.registerEvents()}))}registerEvents(){new RegularEvent("click",(function(){const e=this.dataset.filelistClickTarget;document.querySelector(e).click()})).delegateTo(document.body,'[data-filelist-click-target]:not([data-filelist-click-target=""]'),new RegularEvent("change",(function(){const e=this.dataset.filelistChangeTarget;document.querySelector(e).value=this.value})).delegateTo(document.body,'[data-filelist-change-target]:not([data-filelist-change-target=""])')}}export default new FileReplace;