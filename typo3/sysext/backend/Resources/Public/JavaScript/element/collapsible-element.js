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
import RegularEvent from"@typo3/core/event/regular-event.js";import Icons from"@typo3/backend/icons.js";var IconIdentifier;!function(e){e.collapse="actions-view-list-collapse",e.expand="actions-view-list-expand"}(IconIdentifier||(IconIdentifier={}));class CollapsibleElement extends HTMLElement{connectedCallback(){this.registerEventHandler()}registerEventHandler(){new RegularEvent("click",this.toggleGroup).delegateTo(this,'button[data-bs-toggle="collapse"]')}toggleGroup(e,n){e.preventDefault();const t="true"===n.ariaExpanded,l=n.querySelector(".collapseIcon"),o=t?IconIdentifier.collapse:IconIdentifier.expand;Icons.getIcon(o,Icons.sizes.small).then((e=>{l.innerHTML=e}))}}window.customElements.define("typo3-backend-collapsible",CollapsibleElement);