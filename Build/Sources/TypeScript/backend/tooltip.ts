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

import {Tooltip as BootstrapTooltip} from 'bootstrap';
import DocumentService from '@typo3/core/document-service';

/**
 * The main tooltip object
 *
 * Hint: Due to the current usage of tooltips, this class can't be static right now
 */
class Tooltip {
  private static applyAttributes(attributes: { [key: string]: string }, node: HTMLElement): void {
    for (const [attribute, value] of Object.entries(attributes)) {
      node.setAttribute(attribute, value);
    }
  }

  constructor() {
    DocumentService.ready().then((): void => {
      this.initialize('[data-bs-toggle="tooltip"]');
    });
  }

  public initialize(selector: string, options: Partial<BootstrapTooltip.Options> = {}): void {
    if (Object.entries(options).length === 0) {
      options = {
        container: 'body',
        trigger: 'hover',
        delay: {
          show: 500,
          hide: 100
        }
      }
    }
    const elements = document.querySelectorAll(selector);
    for (const element of elements) {
      // Ensure elements are not initialized multiple times.
      BootstrapTooltip.getOrCreateInstance(element, options);
    }
  }

  /**
   * Show tooltip on element(s)
   *
   * @param {NodeListOf<HTMLElement>|HTMLElement} elements
   * @param {String} title
   */
  public show(elements: NodeListOf<HTMLElement> | HTMLElement, title: string): void {
    const attributes = {
      'data-bs-placement': 'auto',
      title: title
    };

    if (elements instanceof NodeList) {
      for (const node of elements) {
        Tooltip.applyAttributes(attributes, node);
        BootstrapTooltip.getInstance(node).show();
      }
      return;
    }

    if (elements instanceof HTMLElement) {
      Tooltip.applyAttributes(attributes, elements);
      BootstrapTooltip.getInstance(elements).show();
      return;
    }
  }

  /**
   * Hide tooltip on element(s)
   *
   * @param {NodeListOf<HTMLElement>|HTMLElement} elements
   */
  public hide(elements: NodeListOf<HTMLElement> | HTMLElement): void {
    if (elements instanceof NodeList) {
      for (const node of elements) {
        const instance = BootstrapTooltip.getInstance(node);
        if (instance !== null) {
          instance.hide();
        }
      }
      return;
    }

    if (elements instanceof HTMLElement) {
      BootstrapTooltip.getInstance(elements).hide();
      return;
    }
  }
}

const tooltipObject = new Tooltip();

// expose as global object
TYPO3.Tooltip = tooltipObject;

export default tooltipObject;
