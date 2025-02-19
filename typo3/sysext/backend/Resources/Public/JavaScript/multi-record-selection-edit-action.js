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
import r from"@typo3/core/event/regular-event.js";import{MultiRecordSelectionAction as c}from"@typo3/backend/multi-record-selection-action.js";class l{constructor(){new r("multiRecordSelection:action:edit",this.edit).bindTo(document)}edit(t){t.preventDefault();const e=t.detail,i=c.getEntityIdentifiers(e);if(!i.length)return;const n=e.configuration,o=n.tableName||"";o!==""&&(window.location.href=top.TYPO3.settings.FormEngine.moduleUrl+"&edit["+o+"]["+i.join(",")+"]=edit&returnUrl="+encodeURIComponent(n.returnUrl||""))}}var d=new l;export{d as default};
