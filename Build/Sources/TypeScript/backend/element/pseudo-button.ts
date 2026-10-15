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
import { html, css, LitElement, type PropertyValues, type TemplateResult } from 'lit';
import { KeyTypesEnum } from '@typo3/backend/enum/key-types';

/**
 * @internal
 */
export abstract class PseudoButtonLitElement extends LitElement {
  static override styles = [
    css`:host { cursor: pointer; appearance: button; }`,
    css`:host([disabled]) { cursor: default; }`
  ];

  // eslint-disable-next-line lit/no-native-attributes
  @property({ type: String, reflect: true }) override role: string = 'button';
  @property({ type: String, reflect: true }) override tabIndex: number = 0;
  /** As a disabled <button>: not activated, not in the tab order, announced as disabled */
  @property({ type: Boolean, reflect: true }) disabled: boolean = false;

  /** Of the markup or the element, back once the button is enabled again */
  private enabledTabIndex: number = 0;

  public constructor() {
    super();
    this.addEventListener('click', (e: Event): void => {
      e.preventDefault();
      this.activate(e);
    });
    this.addEventListener('keydown', (e: KeyboardEvent): void => {
      if (e.key === KeyTypesEnum.SPACE) {
        e.preventDefault();
      }
      if (e.key === KeyTypesEnum.ENTER) {
        e.preventDefault();
        this.activate(e);
      }
    });
    this.addEventListener('keyup', (e: KeyboardEvent): void => {
      if (e.key === KeyTypesEnum.SPACE) {
        e.preventDefault();
        this.activate(e);
      }
    });
  }

  protected override willUpdate(changed: PropertyValues<this>): void {
    super.willUpdate(changed);
    // Only a change of the state: a button enabled from the start keeps its tab index
    if (changed.has('disabled') && this.disabled !== Boolean(changed.get('disabled'))) {
      if (this.disabled) {
        this.enabledTabIndex = this.tabIndex;
      }
      this.ariaDisabled = this.disabled ? 'true' : null;
      this.tabIndex = this.disabled ? -1 : this.enabledTabIndex;
    }
  }

  protected override render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private activate(e: Event): void {
    if (!this.disabled) {
      this.buttonActivated(e);
    }
  }

  protected abstract buttonActivated(e: Event): void | Promise<void>;
}
