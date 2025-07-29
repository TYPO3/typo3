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

import { customElement, property } from 'lit/decorators.js';
import { html, LitElement, type TemplateResult } from 'lit';
import { Task } from '@lit/task';
import '@typo3/backend/element/spinner-element';
import '@typo3/backend/element/icon-element';

export const ThumbnailSize = {
  default: 'default',
  small: 'small',
  medium: 'medium',
  large: 'large',
} as const;
type ThumbnailSizeType = typeof ThumbnailSize[keyof typeof ThumbnailSize];

@customElement('typo3-backend-thumbnail')
export class ThumbnailElement extends LitElement {
  @property({ type: String, reflect: true }) url: string;
  @property({ type: String, reflect: true }) size: ThumbnailSizeType = ThumbnailSize.default;
  @property({ type: Boolean, reflect: true }) keepAspectRatio: boolean = false;
  @property({ type: Number, reflect: true }) width: number;
  @property({ type: Number, reflect: true }) height: number;

  private readonly thumbnailTask = new Task(this, {
    task: async ([url, size, keepAspectRatio, width, height]: [string, ThumbnailSizeType, boolean, number, number]): Promise<TemplateResult> => {
      const thumbnailUrl = new URL(url, window.origin);
      thumbnailUrl.searchParams.set('size', size);
      thumbnailUrl.searchParams.set('keepAspectRatio', keepAspectRatio ? '1' : '0');

      const img = new Image();
      img.src = thumbnailUrl.toString();

      if (width > 0) {
        img.width = width;
      }

      if (height > 0 && !keepAspectRatio) {
        // Only set height if we do not want to keep the aspect ratio (image is being cropped)
        img.height = height;
      }

      await new Promise<void>(((resolve, reject) => {
        img.onload = () => resolve();
        img.onerror = () => reject();
      }));
      return html`${img}`;
    },
    args: () => [this.url, this.size, this.keepAspectRatio, this.width, this.height]
  });

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult | symbol {
    return this.thumbnailTask.render({
      pending: () => html`<typo3-backend-spinner size=${this.size}></typo3-backend-size>`,
      complete: (markup) => html`${markup}`,
      error: () => html`<typo3-backend-icon identifier="default-not-found" size="small"></typo3-backend-icon>`
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-thumbnail': ThumbnailElement;
  }
}
