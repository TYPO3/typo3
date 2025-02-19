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
import d from"@typo3/core/document-service.js";import o from"@typo3/backend/modal.js";import{topLevelModuleImport as l}from"@typo3/backend/utility/top-level-module-import.js";import{html as r}from"lit";import{until as m}from"lit/directives/until.js";import p from"@typo3/core/ajax/ajax-request.js";class f{constructor(){this.registerEventListeners()}async registerEventListeners(){await d.ready(),document.querySelectorAll(".t3js-pagetsconfig-includes-modal").forEach(e=>{e.addEventListener("click",s=>{s.preventDefault();const t=o.types.default,a=e.dataset.modalTitle||e.textContent.trim(),n=e.getAttribute("href"),c=o.sizes.large,i=r`${m(this.fetchModalContent(n),r`<div class=modal-loading><typo3-backend-spinner size=large></typo3-backend-spinner></div>`)}`;o.advanced({type:t,title:a,size:c,content:i})})})}async fetchModalContent(e){l("@typo3/backend/code-editor/element/code-mirror-element.js");const t=await(await new p(e).get()).resolve();return r`<typo3-t3editor-codemirror .mode=${{name:"@typo3/backend/code-editor/language/typoscript.js",flags:2,exportName:"typoscript",items:[{type:"invoke",args:[]}]}} nolazyload readonly class="flex-grow-1 mh-100"><textarea readonly disabled class=form-control>${t}</textarea></typo3-t3editor-codemirror>`}}var g=new f;export{g as default};
