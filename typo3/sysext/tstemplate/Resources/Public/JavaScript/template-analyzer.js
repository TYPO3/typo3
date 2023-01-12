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
import DocumentService from"@typo3/core/document-service.js";import{default as Modal}from"@typo3/backend/modal.js";import{topLevelModuleImport}from"@typo3/backend/utility/top-level-module-import.js";import{html}from"lit";import{until}from"lit/directives/until.js";import AjaxRequest from"@typo3/core/ajax/ajax-request.js";class TemplateAnalyzer{constructor(){this.registerEventListeners()}async registerEventListeners(){await DocumentService.ready(),document.querySelectorAll(".t3js-typoscript-analyzer-modal").forEach((e=>{e.addEventListener("click",(t=>{t.preventDefault();const o=Modal.types.default,r=e.dataset.modalTitle||e.textContent.trim(),a=e.getAttribute("href"),l=Modal.sizes.large,i=html`${until(this.fetchModalContent(a),html`<div class="modal-loading"><typo3-backend-spinner size="default"></typo3-backend-spinner></div>`)}`;Modal.advanced({type:o,title:r,size:l,content:i})}))}))}async fetchModalContent(e){topLevelModuleImport("@typo3/t3editor/element/code-mirror-element.js");const t=await new AjaxRequest(e).get(),o=await t.resolve();return html`
      <typo3-t3editor-codemirror .mode="${{name:"@typo3/t3editor/language/typoscript.js",flags:2,exportName:"typoscript",items:[{type:"invoke",args:[]}]}}" nolazyload readonly class="flex-grow-1 mh-100">
        <textarea readonly disabled class="form-control">${o}</textarea>
      </typo3-t3editor-codemirror>
    `}}export default new TemplateAnalyzer;