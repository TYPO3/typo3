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
import a from"@typo3/backend/live-search/live-search-configurator.js";import{html as o}from"lit";import t from"@typo3/backend/window-manager.js";function p(r){a.addRenderer(r,"@typo3/backend/live-search/element/provider/page-provider-result-item.js",e=>o`<typo3-backend-live-search-result-item-page-provider .icon=${e.icon} .itemTitle=${e.itemTitle} .typeLabel=${e.typeLabel} .extraData=${e.extraData}></typo3-backend-live-search-result-item-page-provider>`),a.addInvokeHandler(r,"preview_page",(e,i)=>{t.localOpen(i.url,!0)})}export{p as registerRenderer};
