import DocumentService from '@typo3/core/document-service';
import {default as Modal, ModalElement} from '@typo3/backend/modal';
import {topLevelModuleImport} from '@typo3/backend/utility/top-level-module-import';
import {html, TemplateResult} from 'lit';
import {until} from 'lit/directives/until';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import type {JavaScriptItemPayload} from '@typo3/core/java-script-item-processor';

class TemplateAnalyzer {
  constructor() {
    this.registerEventListeners();
  }

  private async registerEventListeners(): Promise<void> {
    await DocumentService.ready();

    document.querySelectorAll('.t3js-typoscript-analyzer-modal').forEach((link: HTMLAnchorElement): void => {
      link.addEventListener('click', (e: Event): void => {
        e.preventDefault();

        const type = Modal.types.default;
        const title = link.dataset.modalTitle || link.textContent.trim();
        const url = link.getAttribute('href');
        const size = Modal.sizes.large;
        const content = html`${until(
          this.fetchModalContent(url),
          html`<div class="modal-loading"><typo3-backend-spinner size="default"></typo3-backend-spinner></div>`
        )}`;
        const modal = Modal.advanced({type, title, size, content});
      });
    });
  }

  private async fetchModalContent(url: string): Promise<TemplateResult> {
    // no `await` purpose (webcomponents initialize lazily)
    topLevelModuleImport('@typo3/t3editor/element/code-mirror-element.js');

    const response: AjaxResponse = await (new AjaxRequest(url)).get();
    const source: string = await response.resolve();

    const mode: JavaScriptItemPayload = {
      name: '@typo3/t3editor/language/typoscript.js',
      flags: 2 /* FLAG_USE_IMPORTMAP */,
      exportName: 'typoscript',
      items: [
        { type: 'invoke', args: [] }
      ]
    };

    return html`
      <typo3-t3editor-codemirror .mode="${mode}" nolazyload readonly class="flex-grow-1 mh-100">
        <textarea readonly disabled class="form-control">${source}</textarea>
      </typo3-t3editor-codemirror>
    `;
  }
}

export default new TemplateAnalyzer();
