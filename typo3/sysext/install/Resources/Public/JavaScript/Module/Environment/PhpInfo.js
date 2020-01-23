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
define(["require","exports","TYPO3/CMS/Backend/Notification","TYPO3/CMS/Core/Ajax/AjaxRequest","../../Router","../AbstractInteractableModule"],(function(e,t,a,n,r,o){"use strict";class c extends o.AbstractInteractableModule{initialize(e){this.currentModal=e,this.getData()}getData(){const e=this.getModalBody();new n(r.getUrl("phpInfoGetData")).get({cache:"no-cache"}).then(async t=>{const n=await t.resolve();!0===n.success?e.empty().append(n.html):a.error("Something went wrong")},t=>{r.handleAjaxError(t,e)})}}return new c}));