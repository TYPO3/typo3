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
import RegularEvent from"@typo3/core/event/regular-event.js";import{MultiRecordSelectionAction}from"@typo3/backend/multi-record-selection-action.js";class MultiRecordSelectionEditAction{constructor(){new RegularEvent("multiRecordSelection:action:edit",this.edit).bindTo(document)}edit(e){e.preventDefault();const t=e.detail,o=MultiRecordSelectionAction.getEntityIdentifiers(t);if(!o.length)return;const n=t.configuration,i=n.tableName||"";""!==i&&(window.location.href=top.TYPO3.settings.FormEngine.moduleUrl+"&edit["+i+"]["+o.join(",")+"]=edit&returnUrl="+encodeURIComponent(n.returnUrl||""))}}export default new MultiRecordSelectionEditAction;