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
import m from"@typo3/core/event/regular-event.js";import{MultiRecordSelectionAction as u}from"@typo3/backend/multi-record-selection-action.js";import f from"@typo3/backend/modal.js";import{SeverityEnum as c}from"@typo3/backend/enum/severity.js";import g from"@typo3/backend/severity.js";import b from"@typo3/backend/ajax-data-handler.js";import p from"@typo3/backend/notification.js";class h{constructor(){new m("multiRecordSelection:action:delete",this.delete).bindTo(document)}delete(t){t.preventDefault();const r=t.detail,n=u.getEntityIdentifiers(r);if(!n.length)return;const e=r.configuration,i=e.tableName||"";if(i==="")return;const a=e.returnUrl||"";f.advanced({title:e.title||"Delete",content:e.content||"Are you sure you want to delete those records?",severity:c.warning,buttons:[{text:e.cancel||TYPO3.lang["button.cancel"]||"Cancel",active:!0,btnClass:"btn-default",name:"cancel",trigger:(s,o)=>o.hideModal()},{text:e.ok||TYPO3.lang["button.delete"]||"OK",btnClass:"btn-"+g.getCssClass(c.warning),name:"delete",trigger:async(s,o)=>{o.hideModal();try{const l=await b.process({cmd:{[i]:Object.fromEntries(n.map(d=>[d,{delete:1}]))}});if(l.hasErrors)throw l.messages;a!==""?t.target.ownerDocument.location.href=a:t.target.ownerDocument.location.reload()}catch{p.error("Could not delete records")}}}]})}}var w=new h;export{w as default};
