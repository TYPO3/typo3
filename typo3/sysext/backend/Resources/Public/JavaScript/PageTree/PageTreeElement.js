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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","lit-html","lit-element","TYPO3/CMS/Core/lit-helper","TYPO3/CMS/Backend/PageTree/PageTree","./PageTreeDragDrop","../Viewport","./PageTreeToolbar","TYPO3/CMS/Core/Ajax/AjaxRequest"],(function(e,t,r,a,i,o,l,n,s,d){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.PageTreeElement=void 0,n=__importDefault(n),d=__importDefault(d);class c{static initialize(e){const t=document.querySelector(e);if(t&&t.childNodes.length>0)return void t.querySelector(".svg-tree").dispatchEvent(new Event("svg-tree:visible"));r.render(c.renderTemplate(),t);const a=t.querySelector(".svg-tree-wrapper"),i=new o,g=new l.PageTreeDragDrop(i),p=top.TYPO3.settings.ajaxUrls.page_tree_configuration;new d.default(p).get().then(async e=>{const r=await e.resolve("json"),o=top.TYPO3.settings.ajaxUrls.page_tree_data,l=top.TYPO3.settings.ajaxUrls.page_tree_filter;Object.assign(r,{dataUrl:o,filterUrl:l,showIcons:!0}),i.initialize(a,g,r),n.default.NavigationContainer.setComponentInstance(i);const d=t.querySelector(".svg-toolbar");if(!d.dataset.treeShowToolbar){new s.PageTreeToolbar(g).initialize(a,d),d.dataset.treeShowToolbar="true"}})}static renderTemplate(){return a.html`
      <div id="typo3-pagetree" class="svg-tree">
        <div>
          <div id="typo3-pagetree-toolbar" class="svg-toolbar"></div>
          <div id="typo3-pagetree-treeContainer" class="navigation-tree-container">
            <div id="typo3-pagetree-tree" class="svg-tree-wrapper">
              <div class="node-loader">
                ${i.icon("spinner-circle-light","small")}
              </div>
            </div>
          </div>
        </div>
        <div class="svg-tree-loader">
          ${i.icon("spinner-circle-light","large")}
        </div>
      </div>
    `}}t.PageTreeElement=c}));