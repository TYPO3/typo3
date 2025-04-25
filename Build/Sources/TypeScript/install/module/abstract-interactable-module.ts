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

import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';
import type { ModalElement } from '@typo3/backend/modal';
import type { WritablePart } from '@typo3/core/utility/types';
import type { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';

enum Identifiers {
  modalBody = '.t3js-modal-body',
  modalContent = '.t3js-module-content',
  modalFooter = '.t3js-modal-footer'
}

export type ModuleLoadedResponse = {
  success: boolean,
  html: string,
};

export type ModuleLoadedResponseWithButtons = ModuleLoadedResponse & {
  buttons: { btnClass: string, text: string }[]
};

export abstract class AbstractInteractableModule {
  protected currentModal: ModalElement;

  public initialize(currentModal: ModalElement): void {
    this.currentModal = currentModal;
  }

  protected getModalBody(): HTMLElement {
    return this.findInModal(Identifiers.modalBody);
  }

  protected getModuleContent(): HTMLElement {
    return this.findInModal(Identifiers.modalContent);
  }

  protected getModalFooter(): HTMLElement {
    return this.findInModal(Identifiers.modalFooter);
  }

  protected findInModal<K extends keyof HTMLElementTagNameMap>(selector: K): HTMLElementTagNameMap[K] | null;
  protected findInModal<K extends keyof SVGElementTagNameMap>(selector: K): SVGElementTagNameMap[K] | null;
  protected findInModal(selector: string): HTMLElement | null;

  protected findInModal(selector: string): HTMLElement | null {
    return this.currentModal.querySelector<HTMLElement>(selector);
  }

  protected setModalButtonsState(interactable: boolean): void {
    this.getModalFooter()?.querySelectorAll('button').forEach((elem: HTMLButtonElement): void => {
      this.setModalButtonState(elem, interactable);
    });
  }

  protected setModalButtonState(button: HTMLButtonElement, interactable: boolean): void {
    button.classList.toggle('disabled', !interactable);
    button.disabled = !interactable;
  }

  protected async loadModuleFrameAgnostic(module: string): Promise<any> {
    const isInIframe = window.location !== window.parent.location;
    if (isInIframe) {
      await topLevelModuleImport(module);
    } else {
      await import(module);
    }
  }

  protected renderProgressBar(
    target?: HTMLElement,
    properties?: Partial<WritablePart<ProgressBarElement>>,
    mode?: 'replace' | 'prepend' | 'append'
  ): ProgressBarElement {
    this.loadModuleFrameAgnostic('@typo3/backend/element/progress-bar-element.js');

    target = target || this.currentModal;

    const progressBar = target.ownerDocument.createElement('typo3-backend-progress-bar');
    if (typeof properties === 'object') {
      Object.keys(properties).forEach((key: keyof WritablePart<ProgressBarElement>) => {
        (progressBar[key] as unknown) = properties[key];
      });
    }

    if (mode === 'append') {
      target.append(progressBar);
    } else if (mode === 'prepend') {
      target.prepend(progressBar);
    } else {
      target.replaceChildren(progressBar);
    }

    return progressBar;
  }
}
