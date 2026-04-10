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
import c from"@typo3/core/event/regular-event.js";import{MultiRecordSelectionAction as d}from"@typo3/backend/multi-record-selection-action.js";import{topWindow as r}from"@typo3/core/utility/top-window.js";class l{constructor(){new c("multiRecordSelection:action:edit",this.edit).bindTo(document)}edit(t){t.preventDefault();const e=t.detail,o=d.getEntityIdentifiers(e);if(!o.length)return;const n=e.configuration,i=n.tableName||"";i!==""&&(window.location.href=r.TYPO3.settings.FormEngine.moduleUrl+"&edit["+i+"]["+o.join(",")+"]=edit&module="+encodeURIComponent(r.TYPO3.ModuleMenu.App.getCurrentModule())+"&returnUrl="+encodeURIComponent(n.returnUrl||""))}}var u=new l;export{u as default};
