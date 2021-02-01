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

import {render} from 'lit-html';
import {html, TemplateResult} from 'lit-element';
import {icon} from 'TYPO3/CMS/Core/lit-helper';
import PageTree = require('TYPO3/CMS/Backend/PageTree/PageTree');
import viewPort from '../Viewport';
import {PageTreeToolbar} from './PageTreeToolbar';
import AjaxRequest from 'TYPO3/CMS/Core/Ajax/AjaxRequest';
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';

/**
 * @exports TYPO3/CMS/Backend/PageTree/PageTreeElement
 */
export class PageTreeElement {
  public static initialize(selector: string): void {
    const targetEl = document.querySelector(selector);

    // let SvgTree know it shall be visible
    if (targetEl && targetEl.childNodes.length > 0) {
      targetEl.querySelector('.svg-tree').dispatchEvent(new Event('svg-tree:visible'));
      return;
    }

    render(PageTreeElement.renderTemplate(), targetEl);
    const treeEl = <HTMLElement>targetEl.querySelector('.svg-tree-wrapper');

    const tree = new PageTree();
    const configurationUrl = top.TYPO3.settings.ajaxUrls.page_tree_configuration;
    (new AjaxRequest(configurationUrl)).get()
      .then(async (response: AjaxResponse): Promise<void> => {
        const configuration = await response.resolve('json');
        const dataUrl = top.TYPO3.settings.ajaxUrls.page_tree_data;
        const filterUrl = top.TYPO3.settings.ajaxUrls.page_tree_filter;
        Object.assign(configuration, {
          dataUrl: dataUrl,
          filterUrl: filterUrl,
          showIcons: true
        });
        tree.initialize(treeEl, configuration);
        viewPort.NavigationContainer.setComponentInstance(tree);
        // the toolbar relies on settings retrieved in this step
        const toolbar = <HTMLElement>targetEl.querySelector('.svg-toolbar');
        if (!toolbar.dataset.treeShowToolbar) {
          const pageTreeToolbar = new PageTreeToolbar();
          pageTreeToolbar.initialize(treeEl, toolbar);
          toolbar.dataset.treeShowToolbar = 'true';
        }
      });
  }

  private static renderTemplate(): TemplateResult {
    return html`
      <div id="typo3-pagetree" class="svg-tree">
        <div>
          <div id="typo3-pagetree-toolbar" class="svg-toolbar"></div>
          <div id="typo3-pagetree-treeContainer" class="navigation-tree-container">
            <div id="typo3-pagetree-tree" class="svg-tree-wrapper">
              <div class="node-loader">
                ${icon('spinner-circle-light', 'small')}
              </div>
            </div>
          </div>
        </div>
        <div class="svg-tree-loader">
          ${icon('spinner-circle-light', 'large')}
        </div>
      </div>
    `;
  }
}
