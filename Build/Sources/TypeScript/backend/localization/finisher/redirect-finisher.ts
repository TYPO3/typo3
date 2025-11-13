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

import { html, type TemplateResult } from 'lit';
import type { FinisherConfig, LocalizationFinisherInterface } from '../localization-finisher';

/**
 * Redirect finisher - navigates to a specific URL after localization
 */
export default class RedirectFinisher implements LocalizationFinisherInterface {
  private config!: FinisherConfig;

  public setConfig(config: FinisherConfig): void {
    this.config = config;
  }

  async render(): Promise<TemplateResult> {
    return html`
      <typo3-backend-alert
        severity="0"
        heading="${this.config.labels.successTitle}"
        message="${this.config.labels.successDescription}"
        show-icon
      ></typo3-backend-alert>
    `;
  }

  async execute(): Promise<void> {
    const url = this.config.data.url as string;

    if (!url) {
      console.warn('Redirect finisher called without URL');
      return;
    }

    if (window.opener && !window.opener.closed && window.self === window.top) {
      window.opener.location.href = url;
      window.close();
    } else if (window.top && window.top.TYPO3?.Backend?.ContentContainer) {
      window.top.TYPO3.Backend.ContentContainer.setUrl(url);
    } else {
      window.location.href = url;
    }
  }
}
