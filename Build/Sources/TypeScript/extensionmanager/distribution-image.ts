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

/**
 * Module @typo3/extensionmanager/distribution-image
 *
 * @example
 * <typo3-extensionmanager-distribution-image image="some/image.jpg" fallback="/some/fallback/image.jpg"/>
 *
 * This is based on W3C custom elements ("web components") specification, see
 * https://developer.mozilla.org/en-US/docs/Web/Web_Components/Using_custom_elements
 */
export class DistributionImage extends HTMLElement {
  private image: string;
  private welcomeImage: string;
  private fallback: string;
  private imageElement: HTMLImageElement;

  public connectedCallback(): void {
    this.image = this.getAttribute('image') || '' as string;
    this.welcomeImage = this.getAttribute('welcomeImage') || '' as string;
    this.fallback = this.getAttribute('fallback') || '' as string;

    if (!this.image.length && !this.fallback.length) {
      return;
    }

    this.attachShadow({mode: 'open'});
    this.imageElement = document.createElement('img');

    const alt: string = this.getAttribute('alt') || '';
    if (alt.length) {
      this.imageElement.setAttribute('alt', alt)
    }

    const title: string = this.getAttribute('title') || '';
    if (title.length) {
      this.imageElement.setAttribute('title', title)
    }

    if (this.welcomeImage.length) {
      this.imageElement.addEventListener('error', this.onError);
      this.imageElement.setAttribute('src', this.welcomeImage);
    } else if (this.image.length) {
      this.imageElement.addEventListener('error', this.onError);
      this.imageElement.setAttribute('src', this.image);
    } else {
      this.imageElement.setAttribute('src', this.fallback);
    }

    const style: HTMLStyleElement = document.createElement('style');
    style.textContent = `
      img {
        display: block;
        width: 300px;
        height: auto;
      }
    `;

    this.shadowRoot.append(this.imageElement, style);
  }

  public disconnectedCallback(): void {
    if (this.image.length && this.imageElement !== null) {
      this.imageElement.removeEventListener('error', this.onError);
    }
  }

  private onError = () => {
    if (this.image.length && this.imageElement.getAttribute('src') === this.welcomeImage) {
      this.imageElement.setAttribute('src', this.image);
    } else if (this.fallback.length) {
      this.imageElement.setAttribute('src', this.fallback);
    }
  }
}

window.customElements.define('typo3-extensionmanager-distribution-image', DistributionImage);
