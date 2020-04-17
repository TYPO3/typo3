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
  protected currentModal: JQuery;
  private readonly selectorModalBody: string = '.t3js-modal-body';
  private readonly selectorModalContent: string = '.t3js-module-content';
  private readonly selectorModalFooter: string = '.t3js-modal-footer';

  protected getModalBody(): JQuery {
    return this.findInModal(this.selectorModalBody);
  }

  protected getModuleContent(): JQuery {
    return this.findInModal(this.selectorModalContent);
  }

  protected getModalFooter(): JQuery {
    return this.findInModal(this.selectorModalFooter);
  }

  protected findInModal(selector: string): JQuery {
    return this.currentModal.find(selector);
  }

  protected setModalButtonsState(interactable: boolean): void {
    this.getModalFooter().find('button').each((_: number, elem: Element): void => {
      this.setModalButtonState($(elem), interactable)
    });
  }

  protected setModalButtonState(button: JQuery, interactable: boolean): void {
    button.toggleClass('disabled', !interactable).prop('disabled', !interactable);
  }

  public abstract initialize(currentModal: JQuery): void;
}
