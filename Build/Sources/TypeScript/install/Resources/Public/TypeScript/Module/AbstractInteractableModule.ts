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

export abstract class AbstractInteractableModule {
  private readonly selectorModalBody: string = '.t3js-modal-body';
  private readonly selectorModalContent: string = '.t3js-module-content';
  protected currentModal: JQuery;

  abstract initialize(currentModal: JQuery): void;

  protected getModalBody(): JQuery {
    return this.findInModal(this.selectorModalBody);
  }

  protected getModuleContent(): JQuery {
    return this.findInModal(this.selectorModalContent);
  }

  protected findInModal(selector: string): JQuery {
    return this.currentModal.find(selector);
  }
}
