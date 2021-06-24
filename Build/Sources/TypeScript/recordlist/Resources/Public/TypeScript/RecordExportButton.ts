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

import {html, TemplateResult, LitElement} from 'lit';
import {customElement, property} from 'lit/decorators';
import {SeverityEnum} from 'TYPO3/CMS/Backend/Enum/Severity';
import Severity = require('TYPO3/CMS/Backend/Severity');
import Modal = require('TYPO3/CMS/Backend/Modal');
import {lll} from 'TYPO3/CMS/Core/lit-helper';

enum Selectors {
  formatSelector = '.t3js-record-export-format-selector',
  formatOptions = '.t3js-record-export-format-option'
}

/**
 * Module: TYPO3/CMS/Recordlist/RecordExportButton
 *
 * @example
 * <typo3-recordlist-record-export-button url="/url/to/configuration/form" title="Export records" ok="Export" close="Cancel">
 *   <button>Export records/button>
 * </typo3-recordlist-record-export-button>
 */
@customElement('typo3-recordlist-record-export-button')
class RecordExportButton extends LitElement {
  @property({type: String}) url: string;
  @property({type: String}) title: string;
  @property({type: String}) ok: string;
  @property({type: String}) close: string;

  public constructor() {
    super();
    this.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      this.showExportConfigurationModal();
    });
  }

  protected render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private showExportConfigurationModal(): void {
    if (!this.url) {
      // Don't render modal in case no url is given
      return;
    }

    Modal.advanced({
      content: this.url,
      title: this.title || 'Export record',
      severity: SeverityEnum.notice,
      size: Modal.sizes.small,
      type: Modal.types.ajax,
      buttons: [
        {
          text: this.close || lll('button.close') || 'Close',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (): void => Modal.dismiss(),
        },
        {
          text: this.ok || lll('button.ok') || 'Export',
          btnClass: 'btn-' + Severity.getCssClass(SeverityEnum.info),
          name: 'export',
          trigger: (): void => {
            const form: HTMLFormElement = Modal.currentModal[0].querySelector('form');
            form && form.submit();
            Modal.dismiss();
          }
        }
      ],
      ajaxCallback: (): void => {
        const formatSelect: HTMLSelectElement = Modal.currentModal[0].querySelector(Selectors.formatSelector);
        const formatOptions: NodeListOf<HTMLDivElement> = Modal.currentModal[0].querySelectorAll(Selectors.formatOptions);

        if (formatSelect === null || !formatOptions.length) {
          // Return in case elements do not exist in the ajax loaded modal content
          return;
        }

        formatSelect.addEventListener('change', (e: Event): void => {
          const selectetFormat: string = (<HTMLSelectElement>e.target).value;
          formatOptions.forEach((option: HTMLDivElement) => {
            if (option.dataset.formatname !== selectetFormat) {
              option.classList.add('hide');
            } else {
              option.classList.remove('hide');
            }
          });
        });
      }
    });
  }
}
