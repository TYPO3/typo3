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
import RegularEvent from"@typo3/core/event/regular-event.js";class TranslationStatus{constructor(){this.registerEvents()}registerEvents(){new RegularEvent("click",this.toggleNewButton).delegateTo(document,'input[type="checkbox"][data-lang]')}toggleNewButton(){const t=document.querySelector(`.t3js-language-new[data-lang="${this.dataset.lang}"]`),e=document.querySelectorAll(`input[type="checkbox"][data-lang="${this.dataset.lang}"]:checked`),a=new URL(location.origin+t.dataset.editUrl);e.forEach((t=>{a.searchParams.set(`cmd[pages][${t.dataset.uid}][localize]`,this.dataset.lang)})),t.href=a.toString(),t.classList.toggle("disabled",0===e.length)}}export default new TranslationStatus;