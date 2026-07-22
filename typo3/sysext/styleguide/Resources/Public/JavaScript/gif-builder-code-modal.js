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
import{html as r}from"lit";import d,{Sizes as i}from"@typo3/backend/modal.js";import{SeverityEnum as l}from"@typo3/backend/enum/severity.js";import m from"@typo3/core/event/regular-event.js";new m("click",(n,e)=>{n.preventDefault();const t=e.dataset.codeTarget;if(!t)return;const o=document.getElementById(t);if(!(o instanceof HTMLTemplateElement))return;const a=(o.content.textContent??"").trim(),c=`language-${e.dataset.codeLanguage??""}`;d.advanced({title:e.dataset.codeTitle,severity:l.notice,size:i.default,content:r`<pre class=${c}>${a}</pre>`})}).delegateTo(document,".t3js-gif-builder-code-trigger");
