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

import { FilePasteHandler, type FilePasteTarget } from '@typo3/backend/file-paste-handler.js';
import Modal, { type ModalElement } from '@typo3/backend/modal.js';
import { expect } from '@open-wc/testing';
import { stub, type SinonStub } from 'sinon';
import { render, type TemplateResult } from 'lit';
import type { } from 'mocha';

interface RecordingTarget extends FilePasteTarget {
  received: File[][];
}

describe('@typo3/backend/file-paste-handler', () => {
  let container: HTMLElement;
  let handler: FilePasteHandler;
  let modalStub: SinonStub;

  const createScope = (parent: HTMLElement = container): HTMLElement => {
    const scope = document.createElement('div');
    scope.append(document.createElement('button'));
    parent.append(scope);
    return scope;
  };

  const createTarget = (scope: HTMLElement | null, label: string = '', available: boolean = true): RecordingTarget => {
    const target: RecordingTarget = {
      scope,
      label,
      received: [],
      isAvailable: (): boolean => available,
      receive: (files: File[]): void => {
        target.received.push(files);
      },
    };
    handler.register(target);
    return target;
  };

  const interactWith = (element: Element): void => {
    element.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
  };

  const paste = (element: Element = container, fileNames: string[] = ['pasted.png']): ClipboardEvent => {
    const clipboardData = new DataTransfer();
    for (const fileName of fileNames) {
      clipboardData.items.add(new File(['x'], fileName, { type: 'image/png' }));
    }
    const event = new ClipboardEvent('paste', { clipboardData, bubbles: true, cancelable: true });
    element.dispatchEvent(event);
    return event;
  };

  beforeEach((): void => {
    container = document.createElement('div');
    document.body.append(container);
    handler = new FilePasteHandler();
    handler.listen(container);
    modalStub = stub(Modal, 'advanced').callsFake((configuration): ModalElement => {
      const modal = document.createElement('div') as unknown as ModalElement;
      modal.hideModal = (): void => modal.remove();
      render(configuration.content as TemplateResult, modal);
      document.body.append(modal);
      return modal;
    });
  });

  afterEach((): void => {
    modalStub.restore();
    container.remove();
  });

  it('passes pasted files to a target of the whole document', (): void => {
    const target = createTarget(null);

    const event = paste();

    expect(target.received).to.have.length(1);
    expect(target.received[0].map((file: File): string => file.name)).to.deep.equal(['pasted.png']);
    expect(event.defaultPrevented).to.be.true;
  });

  it('ignores pastes into form fields', (): void => {
    const target = createTarget(null);
    const input = document.createElement('input');
    container.append(input);

    const event = paste(input);

    expect(target.received).to.have.length(0);
    expect(event.defaultPrevented).to.be.false;
  });

  it('ignores pastes without files', (): void => {
    const target = createTarget(null);

    container.dispatchEvent(new ClipboardEvent('paste', { clipboardData: new DataTransfer(), bubbles: true, cancelable: true }));

    expect(target.received).to.have.length(0);
  });

  it('passes pasted files to the only available target', (): void => {
    const target = createTarget(createScope());
    createTarget(createScope(), '', false);

    paste();

    expect(target.received).to.have.length(1);
  });

  it('passes pasted files to the target interacted with last', (): void => {
    createTarget(createScope());
    const scope = createScope();
    const target = createTarget(scope);

    interactWith(scope.querySelector('button'));
    paste();

    expect(target.received).to.have.length(1);
    expect(modalStub.called).to.be.false;
  });

  it('passes pasted files to the innermost target interacted with last', (): void => {
    const outerScope = createScope();
    const outerTarget = createTarget(outerScope);
    const innerScope = createScope(outerScope);
    const innerTarget = createTarget(innerScope);

    interactWith(innerScope.querySelector('button'));
    paste();

    expect(innerTarget.received).to.have.length(1);
    expect(outerTarget.received).to.have.length(0);
  });

  it('asks for the target if several are available', (): void => {
    const firstTarget = createTarget(createScope(), 'first');
    const secondTarget = createTarget(createScope(), 'second');

    interactWith(container);
    const event = paste(container, ['first.png', 'second.png']);

    expect(event.defaultPrevented).to.be.true;
    expect(modalStub.calledOnce).to.be.true;
    const choices = [...modalStub.firstCall.returnValue.querySelectorAll('button')] as HTMLButtonElement[];
    expect(choices.map((choice: HTMLButtonElement): string => choice.textContent)).to.deep.equal(['first', 'second']);

    choices[1].click();

    expect(firstTarget.received).to.have.length(0);
    expect(secondTarget.received).to.have.length(1);
    expect(secondTarget.received[0].map((file: File): string => file.name)).to.deep.equal(['first.png', 'second.png']);
  });

  it('forgets targets whose scope was removed', (): void => {
    const removedScope = createScope();
    createTarget(removedScope);
    const target = createTarget(createScope());

    removedScope.remove();
    paste();

    expect(target.received).to.have.length(1);
    expect(modalStub.called).to.be.false;
  });
});
