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

import { Popover as BootstrapPopover } from 'bootstrap';

/**
 * Module: @typo3/backend/popover
 * API for popover windows powered by Twitter Bootstrap.
 * @exports @typo3/backend/popover
 */
class Popover {

  /**
   * Default selector string.
   *
   * @return {string}
   */
  private readonly DEFAULT_SELECTOR: string = '[data-bs-toggle="popover"]';

  constructor() {
    this.initialize();
  }

  /**
   * Initialize
   */
  public initialize(selector?: string): void {
    selector = selector || this.DEFAULT_SELECTOR;
    document.querySelectorAll(selector).forEach((element: HTMLElement): void => {
      this.applyTitleIfAvailable(element);
      new BootstrapPopover(element);
    });
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Popover wrapper function
   */
  public popover(element: NodeListOf<HTMLElement> | HTMLElement) {
    this.toIterable(element).forEach((element: HTMLElement): void => {
      this.applyTitleIfAvailable(element);
      new BootstrapPopover(element);
    });
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Set popover options on $element
   *
   * @param {element: HTMLElement} element
   * @param {BootstrapPopover.Options} options
   */
  public setOptions(element: HTMLElement, options?: BootstrapPopover.Options): void {
    options = options || <BootstrapPopover.Options>{};
    const title: string = (options.title as string) || element.dataset.title || element.dataset.bsTitle || '';
    const content: string = (options.content as string) || element.dataset.bsContent || '';
    element.dataset.bsTitle = title;
    element.dataset.bsOriginalTitle = title;
    element.dataset.bsContent = content;
    element.dataset.bsPlacement = 'auto';

    delete options.title;
    delete options.content;

    const popover = BootstrapPopover.getInstance(element);
    popover.setContent({
      '.popover-header': title,
      '.popover-body': content
    });

    for (const [optionName, optionValue] of Object.entries(options)) {
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore: using internal _config attribute
      popover._config[optionName] = optionValue;
    }
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Show popover with title and content on $element
   *
   * @param {element: HTMLElement} element
   */
  public show(element: HTMLElement): void {
    const popover = BootstrapPopover.getInstance(element);
    popover.show();
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Hide popover on $element
   *
   * @param {HTMLElement} element
   */
  public hide(element: HTMLElement): void {
    const popover = BootstrapPopover.getInstance(element);
    popover.hide();
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Destroy popover on $element
   *
   * @param {HTMLElement} element
   */
  public destroy(element: HTMLElement): void {
    const popover = BootstrapPopover.getInstance(element);
    popover.dispose();
  }

  // noinspection JSMethodCanBeStatic
  /**
   * Toggle popover on $element
   *
   * @param {HTMLElement} element
   */
  public toggle(element: HTMLElement): void {
    const popover = BootstrapPopover.getInstance(element);
    popover.toggle();
  }

  private toIterable(element: NodeListOf<HTMLElement> | HTMLElement | unknown): NodeList | HTMLElement[] {
    let elementList;
    if (element instanceof HTMLElement) {
      elementList = [element];
    } else if (element instanceof NodeList) {
      elementList = element;
    } else {
      throw `Cannot consume element of type ${element.constructor.name}, expected NodeListOf<HTMLElement> or HTMLElement`;
    }

    return elementList;
  }

  /**
   * If the element contains an attributes that qualifies as a title, store it as data attribute "bs-title"
   */
  private applyTitleIfAvailable(element: HTMLElement): void {
    const title = (element.title as string) || element.dataset.title || '';
    if (title) {
      element.dataset.bsTitle = title;
    }
  }
}

export default new Popover();
