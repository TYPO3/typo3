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

import { html } from 'lit';
import { default as Modal, Sizes as ModalSizes } from './modal';
import { SeverityEnum } from './enum/severity';
import coreLabels from '~labels/core.core';

export interface FilePasteTarget {
  readonly scope: HTMLElement | null;
  readonly label: string;
  isAvailable(): boolean;
  receive(files: File[]): void;
}

/**
 * Module: @typo3/backend/file-paste-handler
 * Passes files pasted from the clipboard to one of the registered targets, e.g. the uploader of a file field.
 */
export class FilePasteHandler {
  private readonly targets = new Set<FilePasteTarget>();

  /**
   * Element the user clicked or focused last. Clicking a button does not focus it in every browser,
   * so the focused element alone does not tell which target a paste is meant for.
   */
  private lastInteractionTarget: Element | null = null;

  public listen(eventTarget: GlobalEventHandlers): void {
    eventTarget.addEventListener('pointerdown', this.trackInteraction, { capture: true });
    eventTarget.addEventListener('focusin', this.trackInteraction, { capture: true });
    eventTarget.addEventListener('paste', this.handlePaste);
  }

  public register(target: FilePasteTarget): void {
    this.targets.add(target);
  }

  private readonly trackInteraction = (event: Event): void => {
    this.lastInteractionTarget = event.target instanceof Element ? event.target : null;
  };

  private readonly handlePaste = (event: ClipboardEvent): void => {
    const files = event.clipboardData?.files;
    if (event.defaultPrevented || !files || files.length === 0) {
      return;
    }
    // Pasting into a form field is left to the field
    if (event.target instanceof Element && event.target.closest('input, textarea, select, [contenteditable]:not([contenteditable="false"])') !== null) {
      return;
    }
    const candidates = this.findCandidates();
    if (candidates.length === 0) {
      return;
    }
    event.preventDefault();
    // The clipboard data is emptied once the event has been dispatched
    const pastedFiles = Array.from(files);
    if (candidates.length === 1) {
      candidates[0].receive(pastedFiles);
    } else {
      this.askForTarget(candidates, pastedFiles);
    }
  };

  /**
   * The innermost target the user interacted with last receives a paste, otherwise one of the available targets
   */
  private findCandidates(): FilePasteTarget[] {
    const scopedTargets = new Map<Element, FilePasteTarget>();
    for (const target of this.targets) {
      if (target.scope === null) {
        continue;
      }
      if (target.scope.isConnected) {
        scopedTargets.set(target.scope, target);
      } else {
        this.targets.delete(target);
      }
    }
    for (let element = this.lastInteractionTarget; element !== null; element = element.parentElement) {
      if (scopedTargets.has(element)) {
        return [scopedTargets.get(element)];
      }
    }
    return [...this.targets].filter((target: FilePasteTarget): boolean => target.isAvailable());
  }

  private askForTarget(targets: FilePasteTarget[], files: File[]): void {
    const choose = (target: FilePasteTarget): void => {
      modal.hideModal();
      target.receive(files);
    };
    const modal = Modal.advanced({
      title: coreLabels.get('file_upload.pasteTarget.title'),
      content: html`
        <p>${coreLabels.get('file_upload.pasteTarget.message', { count: files.length })}</p>
        <div class="d-grid gap-2">
          ${targets.map((target: FilePasteTarget) => html`
            <button type="button" class="btn btn-default" @click=${() => choose(target)}>${target.label}</button>
          `)}
        </div>
      `,
      severity: SeverityEnum.notice,
      size: ModalSizes.small,
      buttons: [
        {
          text: coreLabels.get('file_upload.button.cancel'),
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (): void => modal.hideModal(),
        },
      ],
    });
  }
}

const filePasteHandler = new FilePasteHandler();
filePasteHandler.listen(document);

export default filePasteHandler;
