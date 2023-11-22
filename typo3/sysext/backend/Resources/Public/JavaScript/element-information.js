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
import DocumentService from"@typo3/core/document-service.js";import RegularEvent from"@typo3/core/event/regular-event.js";import Icons from"@typo3/backend/icons.js";class ElementInformation{constructor(){DocumentService.ready().then((()=>{document.querySelectorAll("div[data-persist-collapse-state]").forEach((e=>{const t=!e.classList.contains("show"),o=document.querySelector('.t3js-toggle-table[data-bs-target="#'+e.id+'"]');if(null!==o){o.setAttribute("aria-expanded",t?"false":"true"),o.classList.toggle("collapsed",t);const e=o.querySelector(".t3js-icon");null!==e&&this.replaceIcon(t,e)}})),new RegularEvent("show.bs.collapse",this.toggleCollapseIcon.bind(this)).bindTo(document),new RegularEvent("hide.bs.collapse",this.toggleCollapseIcon.bind(this)).bindTo(document)}))}toggleCollapseIcon(e){const t=document.querySelector('.t3js-toggle-table[data-bs-target="#'+e.target.id+'"] .t3js-icon');null!==t&&this.replaceIcon("hide.bs.collapse"===e.type,t)}replaceIcon(e,t){Icons.getIcon(e?"actions-view-list-expand":"actions-view-list-collapse",Icons.sizes.small).then((e=>{t.replaceWith(document.createRange().createContextualFragment(e))}))}}export default new ElementInformation;