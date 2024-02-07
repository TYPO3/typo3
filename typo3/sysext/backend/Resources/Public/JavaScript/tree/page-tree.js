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
import{Tree}from"@typo3/backend/tree/tree.js";import{html}from"lit";export class PageTree extends Tree{constructor(){super(),this.settings.defaultProperties={hasChildren:!1,nameSourceField:"title",prefix:"",suffix:"",locked:!1,loaded:!1,overlayIcon:"",selectable:!0,expanded:!1,checked:!1,stopPageTree:!1}}getDataUrl(e=null){return null===e?this.settings.dataUrl:this.settings.dataUrl+"&parent="+e.identifier+"&mount="+e.mountPoint+"&depth="+e.depth}createNodeToggle(e){const t=this.isRTL()?"actions-caret-left":"actions-caret-right";return html`${e.stopPageTree&&0!==e.depth?html`
          <span class="node-stop" @click="${t=>{t.preventDefault(),t.stopImmediatePropagation(),document.dispatchEvent(new CustomEvent("typo3:pagetree:mountPoint",{detail:{pageId:parseInt(e.identifier,10)}}))}}">
            <typo3-backend-icon identifier="${t}" size="small"></typo3-backend-icon>
          </span>
        `:super.createNodeToggle(e)}`}}