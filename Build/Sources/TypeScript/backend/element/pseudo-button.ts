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

import { property } from 'lit/decorators.js';
import { html, css, LitElement, type TemplateResult } from 'lit';
import { KeyTypesEnum } from '@typo3/backend/enum/key-types';

/**
 * @internal
 */
export abstract class PseudoButtonLitElement extends LitElement {
  static override styles = [
    css`:host { cursor: pointer; appearance: button; }`
  ];

  // eslint-disable-next-line lit/no-native-attributes
  @property({ type: String, reflect: true }) override role: string = 'button';
  @property({ type: String, reflect: true }) override tabIndex: number = 0;

  public constructor() {
    super();
    this.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      this.buttonActivated(e);
    });
    this.addEventListener('keydown', (e: KeyboardEvent): void => {
      if (e.key === KeyTypesEnum.SPACE) {
        e.preventDefault();
      }
      if (e.key === KeyTypesEnum.ENTER) {
        e.preventDefault();
        this.buttonActivated(e);
      }
    });
    this.addEventListener('keyup', (e: KeyboardEvent): void => {
      if (e.key === KeyTypesEnum.SPACE) {
        e.preventDefault();
        this.buttonActivated(e);
      }
    });
  }

  protected override render(): TemplateResult {
    return html`<slot></slot>`;
  }

  protected abstract buttonActivated(e: Event): void | Promise<void>;
}
