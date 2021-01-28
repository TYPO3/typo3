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
var __importDefault=this&&this.__importDefault||function(e){return e&&e.__esModule?e:{default:e}};define(["require","exports","jquery","lit-html","lit-element","TYPO3/CMS/Core/lit-helper","TYPO3/CMS/Backend/PageTree/PageTree","../Viewport","./PageTreeToolbar","TYPO3/CMS/Core/Ajax/AjaxRequest"],(function(e,t,r,i,a,o,l,s,n,d){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),t.PageTreeElement=void 0,r=__importDefault(r),s=__importDefault(s),d=__importDefault(d);class g{static initialize(e){const t=document.querySelector(e);if(t&&t.childNodes.length>0)return void r.default("#typo3-pagetree").trigger("isVisible");i.render(g.renderTemplate(),t);const a=t.querySelector("#typo3-pagetree-tree");r.default(a).on("scroll",()=>{r.default(a).find("[data-bs-toggle=tooltip]").tooltip("hide")});const o=new l,p=top.TYPO3.settings.ajaxUrls.page_tree_configuration;new d.default(p).get().then(async e=>{const t=await e.resolve("json"),r=top.TYPO3.settings.ajaxUrls.page_tree_data,i=top.TYPO3.settings.ajaxUrls.page_tree_filter;Object.assign(t,{dataUrl:r,filterUrl:i,showIcons:!0}),o.initialize(a,t),s.default.NavigationContainer.setComponentInstance(o);const l=document.getElementById("svg-toolbar");if(!l.dataset.treeShowToolbar){(new n.PageTreeToolbar).initialize("#typo3-pagetree-tree"),l.dataset.treeShowToolbar="true"}})}static renderTemplate(){return a.html`
      <div id="typo3-pagetree" class="svg-tree">
        <div>
          <div id="svg-toolbar" class="svg-toolbar"></div>
          <div id="typo3-pagetree-treeContainer">
            <div id="typo3-pagetree-tree" class="svg-tree-wrapper" style="height:1000px;">
              <div class="node-loader">
                ${o.icon("spinner-circle-light","small")}
              </div>
            </div>
          </div>
        </div>
        <div class="svg-tree-loader">
          ${o.icon("spinner-circle-light","large")}
        </div>
      </div>
    `}}t.PageTreeElement=g}));