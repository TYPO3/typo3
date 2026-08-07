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

import DocumentService from '@typo3/core/document-service';

enum Selectors {
  wrapper = '.module-docheader-wrapper',
  navigation = '.t3js-module-docheader-navigation',
}

/**
 * Module: @typo3/backend/module-docheader
 *
 * Publishes the measured docheader heights as CSS custom properties, because
 * both bars wrap and the stylesheet cannot know how much of it stays pinned.
 */
class ModuleDocHeader {
  private readonly resizeObserver: ResizeObserver;
  private wrapper: HTMLElement | null = null;
  private navigation: HTMLElement | null = null;

  constructor() {
    this.resizeObserver = new ResizeObserver((): void => this.publish());
    DocumentService.ready().then((): void => this.observe());
  }

  private observe(): void {
    this.wrapper = document.querySelector(Selectors.wrapper);
    if (this.wrapper === null) {
      return;
    }

    this.navigation = this.wrapper.querySelector(Selectors.navigation);
    // observing delivers an initial record, which is what publishes the first values
    this.resizeObserver.observe(this.wrapper);
    if (this.navigation !== null) {
      this.resizeObserver.observe(this.navigation);
    }
  }

  private publish(): void {
    const style = document.documentElement.style;
    style.setProperty('--module-docheader-height', `${this.wrapper.getBoundingClientRect().height}px`);
    style.setProperty('--module-docheader-navigation-height', `${this.navigation?.getBoundingClientRect().height ?? 0}px`);
  }
}

export default new ModuleDocHeader();
