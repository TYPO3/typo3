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
import type { FinisherInterface } from '@typo3/backend/wizard/finisher/finisher-interface';
import type { FinisherConfig } from '@typo3/backend/wizard/finisher/finisher-config';

/**
 * Event finisher - dispatches the event supplied via `config.data.event` when
 * the wizard finishes.
 *
 * The wizard runs in the top window while backend modules live in content
 * frames, so the event is broadcast to the top document and every child frame,
 * where the module that opened the wizard (e.g. the dashboard) can listen for
 * it on its own document and update in place instead of reloading.
 */
export default class EventFinisher implements FinisherInterface {
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
    const event = this.config.data.event;

    if (!(event instanceof Event)) {
      console.warn('Event finisher called without an event to dispatch');
      return;
    }

    document.dispatchEvent(event);
    for (let i = 0; i < window.frames.length; i++) {
      try {
        window.frames[i].document.dispatchEvent(event);
      } catch {
        // Cross-origin frame, skip
      }
    }
  }
}
