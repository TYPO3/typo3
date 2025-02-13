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

import { css, html, LitElement, nothing, TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators';

/**
 * Module @typo3/extensionmanager/distribution-image
 *
 * @example
 * <typo3-extensionmanager-distribution-image image="some/image.jpg" fallback="/some/fallback/image.jpg"/>
 */
@customElement('typo3-extensionmanager-distribution-image')
export class DistributionImage extends LitElement {
  static override styles = css`
    img {
      display: block;
      width: 100%;
      height: auto;
    }
  `;

  @property({ type: String }) alt: string;
  @property({ type: String }) image: string;
  @property({ type: String }) welcomeImage: string;
  @property({ type: String }) fallback: string;

  protected override render(): TemplateResult|symbol {
    if (!this.image && !this.fallback) {
      return nothing;
    }

    const imageToUse = this.welcomeImage || this.image || this.fallback;
    return html`<img alt="${this.alt}" src="${imageToUse}" @error="${imageToUse !== this.fallback ? this.onError : nothing}">`;
  }

  private onError(e: Event): void {
    const imageElement = e.target as HTMLImageElement;
    if (this.image.length && imageElement.getAttribute('src') === this.welcomeImage) {
      imageElement.setAttribute('src', this.image);
    } else if (this.fallback.length) {
      imageElement.setAttribute('src', this.fallback);
    }
  }
}
