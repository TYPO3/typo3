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

import '../renderable/progress-bar';
import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';
import type { ProgressBar } from '../renderable/progress-bar';
import type { ModalElement } from '@typo3/backend/modal';

type IfEquals<X, Y, A, B> =
  (<T>() => T extends X ? 1 : 2) extends
  (<T>() => T extends Y ? 1 : 2) ? A : B;
type WritableKeysOf<T> = {
  [P in keyof T]: IfEquals<{ [Q in P]: T[P] }, { -readonly [Q in P]: T[P] }, P, never>
}[keyof T];
type WritablePart<T> = Pick<T, WritableKeysOf<T>>;

export abstract class AbstractInteractableModule {
  protected currentModal: ModalElement;
  private readonly selectorModalBody: string = '.t3js-modal-body';
  private readonly selectorModalContent: string = '.t3js-module-content';
  private readonly selectorModalFooter: string = '.t3js-modal-footer';

  public initialize(currentModal: ModalElement): void {
    this.currentModal = currentModal;
  }

  protected getModalBody(): HTMLElement {
    return this.findInModal(this.selectorModalBody);
  }

  protected getModuleContent(): HTMLElement {
    return this.findInModal(this.selectorModalContent);
  }

  protected getModalFooter(): HTMLElement {
    return this.findInModal(this.selectorModalFooter);
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

  protected renderProgressBar(
    target?: HTMLElement,
    properties?: Partial<WritablePart<ProgressBar>>,
    mode?: 'replace' | 'prepend' | 'append'
  ): ProgressBar {
    const isInIframe = window.location !== window.parent.location;
    if (isInIframe) {
      topLevelModuleImport('@typo3/install/renderable/progress-bar.js');
    }

    target = target || this.currentModal;

    const progressBar = target.ownerDocument.createElement('typo3-install-progress-bar');
    if (typeof properties === 'object') {
      Object.keys(properties).forEach((key: keyof WritablePart<ProgressBar>) => {
        (progressBar[key] as unknown) = properties[key]
      });
    }

    if (mode === 'append') {
      target.append(progressBar)
    } else if (mode === 'prepend') {
      target.prepend(progressBar)
    } else {
      target.replaceChildren(progressBar)
    }
    return progressBar;
  }
}
