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
define(["require","exports","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Router","../AbstractInteractableModule"],(function(e,t,s,a,n,r){"use strict";class o extends r.AbstractInteractableModule{initialize(e){this.currentModal=e,this.getData()}getData(){const e=this.getModalBody();new a(n.getUrl("systemInformationGetData")).get({cache:"no-cache"}).then(async t=>{const a=await t.resolve();!0===a.success?e.empty().append(a.html):s.error("Something went wrong","The request was not processed successfully. Please check the browser's console and TYPO3's log.")},t=>{n.handleAjaxError(t,e)})}}return new o}));