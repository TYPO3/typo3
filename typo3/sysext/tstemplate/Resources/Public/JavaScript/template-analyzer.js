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
import l from"@typo3/core/document-service.js";import o from"@typo3/backend/modal.js";import{topLevelModuleImport as d}from"@typo3/backend/utility/top-level-module-import.js";import{html as r}from"lit";import{until as m}from"lit/directives/until.js";import p from"@typo3/core/ajax/ajax-request.js";class y{constructor(){this.registerEventListeners()}async registerEventListeners(){await l.ready(),document.querySelectorAll(".t3js-typoscript-analyzer-modal").forEach(e=>{e.addEventListener("click",a=>{a.preventDefault();const t=o.types.default,s=e.dataset.modalTitle||e.textContent.trim(),n=e.getAttribute("href"),c=o.sizes.large,i=r`${m(this.fetchModalContent(n),r`<div class=modal-loading><typo3-backend-spinner size=large></typo3-backend-spinner></div>`)}`;o.advanced({type:t,title:s,size:c,content:i})})})}async fetchModalContent(e){d("@typo3/backend/code-editor/element/code-mirror-element.js");const t=await(await new p(e).get()).resolve();return r`<typo3-t3editor-codemirror .mode=${{name:"@typo3/backend/code-editor/language/typoscript.js",flags:2,exportName:"typoscript",items:[{type:"invoke",args:[]}]}} nolazyload readonly class="flex-grow-1 mh-100"><textarea readonly disabled class=form-control>${t}</textarea></typo3-t3editor-codemirror>`}}var f=new y;export{f as default};
