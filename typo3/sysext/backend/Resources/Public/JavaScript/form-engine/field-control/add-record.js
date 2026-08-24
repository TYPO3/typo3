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
import s from"@typo3/core/document-service.js";import a from"@typo3/backend/form-engine.js";class l{constructor(o){this.controlElement=null,this.registerClickHandler=n=>{n.preventDefault();const t=this.controlElement.getAttribute("href"),e=new URL(t,window.location.origin),r=e.searchParams.get("P[uid]")||"";if(r.startsWith("NEW")){a.preventFollowAddRecordLinkIfNotSaved({originalUid:r,ownerTable:e.searchParams.get("P[table]")||"",ownerField:e.searchParams.get("P[field]")||"",table:e.searchParams.get("P[params][table]")||"",pid:e.searchParams.get("P[params][pid]")||"",setValue:e.searchParams.get("P[params][setValue]")||"append",flexFormPath:e.searchParams.get("P[flexFormPath]")||""});return}a.preventFollowLinkIfNotSaved(t)},s.ready().then(()=>{this.controlElement=document.querySelector(o),this.controlElement.addEventListener("click",this.registerClickHandler)})}}export{l as default};
