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

import {html, css, TemplateResult, LitElement} from 'lit';
import {customElement, property} from 'lit/decorators';
import {SeverityEnum} from '@typo3/backend/enum/severity';
import Severity from '@typo3/backend/severity';
import Modal from '@typo3/backend/modal';
import {lll} from '@typo3/core/lit-helper';

enum Selectors {
  formatSelector = '.t3js-record-download-format-selector',
  formatOptions = '.t3js-record-download-format-option'
}

/**
 * Module: @typo3/backend/record-download-button
 *
 * @example
 * <typo3-recordlist-record-download-button url="/url/to/configuration/form" title="Download records" ok="Download" close="Cancel">
 *   <button>Download records/button>
 * </typo3-recordlist-record-download-button>
 */
@customElement('typo3-recordlist-record-download-button')
class RecordDownloadButton extends LitElement {
  static styles = [css`:host { cursor: pointer; appearance: button; }`];
  @property({type: String}) url: string;
  @property({type: String}) title: string;
  @property({type: String}) ok: string;
  @property({type: String}) close: string;

  public constructor() {
    super();
    this.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      this.showDownloadConfigurationModal();
    });
    this.addEventListener('keydown', (e: KeyboardEvent): void => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        this.showDownloadConfigurationModal();
      }
    })
  }

  public connectedCallback(): void {
    if (!this.hasAttribute('role')) {
      this.setAttribute('role', 'button');
    }
    if (!this.hasAttribute('tabindex')) {
      this.setAttribute('tabindex', '0');
    }
  }

  protected render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private showDownloadConfigurationModal(): void {
    if (!this.url) {
      // Don't render modal in case no url is given
      return;
    }

    const modal = Modal.advanced({
      content: this.url,
      title: this.title || 'Download records',
      severity: SeverityEnum.notice,
      size: Modal.sizes.small,
      type: Modal.types.ajax,
      buttons: [
        {
          text: this.close || lll('button.close') || 'Close',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (): void => modal.hideModal(),
        },
        {
          text: this.ok || lll('button.ok') || 'Download',
          btnClass: 'btn-' + Severity.getCssClass(SeverityEnum.info),
          name: 'download',
          trigger: (): void => {
            const form: HTMLFormElement = modal.querySelector('form');
            form && form.submit();
            modal.hideModal();
          }
        }
      ],
      ajaxCallback: (): void => {
        const formatSelect: HTMLSelectElement = modal.querySelector(Selectors.formatSelector);
        const formatOptions: NodeListOf<HTMLDivElement> = modal.querySelectorAll(Selectors.formatOptions);

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
