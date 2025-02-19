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
import o from"@typo3/core/document-service.js";import u from"@typo3/core/event/regular-event.js";var n;(function(s){s.CUSTOM="custom"})(n||(n={}));class c{constructor(r){o.ready().then(()=>{this.registerEventHandler(r)})}registerEventHandler(r){new u("change",i=>{const t=i.target,e=t.closest(".t3js-formengine-field-item")?.querySelector("[data-formengine-input-name]");if(!e)return;const a=e.dataset.lastL10nState||!1,l=t.value;a&&l===a||(l===n.CUSTOM?(a&&(t.dataset.originalLanguageValue=e.value),e.disabled=!1):(a===n.CUSTOM&&(t.closest(".t3js-l10n-state-container").querySelector(".t3js-l10n-state-custom").dataset.originalLanguageValue=e.value),e.disabled=!0),e.value=t.dataset.originalLanguageValue,e.dispatchEvent(new Event("change")),e.dataset.lastL10nState=t.value)}).delegateTo(document,'.t3js-l10n-state-container input[type="radio"][name="'+r+'"]')}}export{c as default};
