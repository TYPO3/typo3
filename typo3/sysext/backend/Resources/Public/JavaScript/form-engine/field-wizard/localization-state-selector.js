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
import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";var States;!function(e){e.CUSTOM="custom"}(States||(States={}));class LocalizationStateSelector{constructor(e){DocumentService.ready().then((()=>{this.registerEventHandler(e)}))}registerEventHandler(e){new RegularEvent("change",(e=>{const t=e.target,a=t.closest(".t3js-formengine-field-item")?.querySelector("[data-formengine-input-name]");if(!a)return;const n=a.dataset.lastL10nState||!1,r=t.value;n&&r===n||(r===States.CUSTOM?(n&&(t.dataset.originalLanguageValue=a.value),a.disabled=!1):(n===States.CUSTOM&&(t.closest(".t3js-l10n-state-container").querySelector(".t3js-l10n-state-custom").dataset.originalLanguageValue=a.value),a.disabled=!0),a.value=t.dataset.originalLanguageValue,a.dispatchEvent(new Event("change")),a.dataset.lastL10nState=t.value)})).delegateTo(document,'.t3js-l10n-state-container input[type="radio"][name="'+e+'"]')}}export default LocalizationStateSelector;