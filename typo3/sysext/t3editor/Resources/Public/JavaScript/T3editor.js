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
define(["require","exports","TYPO3/CMS/Core/DocumentService","./Element/CodeMirrorElement"],(function(e,t,r){"use strict";return new class{static createPanelNode(e,t){const r=document.createElement("div");r.setAttribute("class","CodeMirror-panel CodeMirror-panel-"+e),r.setAttribute("id","panel-"+e);const o=document.createElement("span");return o.textContent=t,r.appendChild(o),r}constructor(){console.warn("TYPO3/CMS/T3editor/T3editor has been marked as deprecated. Please use TYPO3/CMS/T3editor/Element/CodeMirrorElement instead."),this.initialize()}initialize(){r.ready().then(()=>{this.observeEditorCandidates()})}observeEditorCandidates(){document.querySelectorAll("textarea.t3editor").forEach(e=>{if("typo3-t3editor-codemirror"===e.parentElement.tagName.toLowerCase())return;const t=document.createElement("typo3-t3editor-codemirror"),r=JSON.parse(e.getAttribute("data-codemirror-config"));t.setAttribute("mode",r.mode),t.setAttribute("label",r.label),t.setAttribute("addons",r.addons),t.setAttribute("options",r.options),this.wrap(e,t)})}wrap(e,t){e.parentElement.insertBefore(t,e),t.appendChild(e)}}}));