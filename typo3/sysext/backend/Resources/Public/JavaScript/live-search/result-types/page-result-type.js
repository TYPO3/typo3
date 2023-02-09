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
import LiveSearchConfigurator from"@typo3/backend/live-search/live-search-configurator.js";import{html}from"lit";import windowManager from"@typo3/backend/window-manager.js";export function registerRenderer(e){LiveSearchConfigurator.addRenderer(e,"@typo3/backend/live-search/element/provider/page-provider-result-item.js",(e=>html`<typo3-backend-live-search-result-item-page-provider
        .icon="${e.icon}"
        .itemTitle="${e.itemTitle}"
        .typeLabel="${e.typeLabel}"
        .extraData="${e.extraData}">
      </typo3-backend-live-search-result-item-page-provider>`)),LiveSearchConfigurator.addInvokeHandler(e,"preview_page",((e,r)=>{windowManager.localOpen(r.url,!0)}))}