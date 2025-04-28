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

import { customElement, property, query } from 'lit/decorators';
import { css, html, LitElement, type TemplateResult } from 'lit';
import Alwan from 'alwan';
import RegularEvent from '@typo3/core/event/regular-event';
import DocumentService from '@typo3/core/document-service';

@customElement('typo3-backend-color-picker')
export class Typo3BackendColorPicker extends LitElement {
  static override styles = css`
    :host {
      display: inline-block;
      position: relative;
    }

    .color-picker-preview {
      --typo3-colorpicker-preview-width: 1.25rem;
      --typo3-colorpicker-preview-height: 1.25rem;
      --typo3-bg-checkerboard-pattern-size: calc(var(--typo3-colorpicker-preview-width) / 2);
      --typo3-bg-checkerboard-background-color: light-dark(var(--token-color-neutral-10), var(--token-color-neutral-85));
      --typo3-bg-checkerboard-background-image-color: light-dark(var(--token-color-neutral-0), var(--token-color-neutral-90));

      display: block;
      position: absolute;
      width: var(--typo3-colorpicker-preview-width);
      height: var(--typo3-colorpicker-preview-height);
      top: 50%;
      inset-inline-start: var(--typo3-input-sm-padding-x);
      z-index: 1;
      transform: translate(0, -50%);
      background: var(--typo3-bg-checkerboard-background-color);
      background-image: linear-gradient(45deg, var(--typo3-bg-checkerboard-background-image-color) 25%, transparent 25%), linear-gradient(135deg, var(--typo3-bg-checkerboard-background-image-color) 25%, transparent 25%), linear-gradient(45deg, transparent 75%, var(--typo3-bg-checkerboard-background-image-color) 75%), linear-gradient(135deg, transparent 75%, var(--typo3-bg-checkerboard-background-image-color) 75%);
      background-position: 0 0, calc(var(--typo3-bg-checkerboard-pattern-size) / 2) 0, calc(var(--typo3-bg-checkerboard-pattern-size) / 2) calc(var(--typo3-bg-checkerboard-pattern-size) / 2 * -1), 0 calc(var(--typo3-bg-checkerboard-pattern-size) / 2);
      background-size: var(--typo3-bg-checkerboard-pattern-size) var(--typo3-bg-checkerboard-pattern-size);
      background-clip: padding-box;
      border-radius: var(--typo3-component-border-radius);
      pointer-events: none;
    }

    .color-picker-preview-color {
      position: absolute;
      inset: 0;
      border-radius: 2px;
      background-color: var(--color, transparent);
    }
    `;

  @property({ type: String }) color: string = '';
  @property({ type: Boolean }) opacity: boolean = false;
  @property({ type: String }) swatches: string = '';

  // Use a reference to the input slot element
  @query('slot') slotEl!: HTMLSlotElement;

  protected override async firstUpdated(): Promise<void> {
    await DocumentService.ready();

    const inputElement = this.getInputElement();
    if (inputElement) {
      if (!inputElement.value && this.color) {
        inputElement.value = this.color;
      } else {
        this.color = inputElement.value;
      }

      if (inputElement.disabled || inputElement.readOnly) {
        return;
      }

      const alwan = new Alwan(inputElement, {
        position: 'bottom-start',
        format: 'hex',
        opacity: this.opacity,
        swatches: this.swatches ? this.swatches.split(';') : [],
        preset: false,
        color: this.color,
      });

      alwan.on('color', (e): void => {
        this.color = e.hex;
        inputElement.value = this.color;
        inputElement.dispatchEvent(new Event('blur'));
      });

      // input: react on user input
      // change: react on indirect changes, e.g. a value picker
      ['input', 'change'].forEach((eventName: string): void => {
        new RegularEvent(eventName, (e: Event): void => {
          const input = (e.target as HTMLInputElement);
          this.color = input.value;
          alwan.setColor(this.color);
        }).bindTo(inputElement);
      });
    }
  }

  protected override render(): TemplateResult {
    return html`
      <slot></slot>
      <span style="--color: ${this.color}" class="color-picker-preview"><span class="color-picker-preview-color"></span></span>
    `;
  }

  private getInputElement(): HTMLInputElement | null {
    const assignedNodes = this.slotEl.assignedNodes();
    for (const node of assignedNodes) {
      if (node instanceof HTMLInputElement) {
        return node;
      }
    }
    console.warn('No input element found in the slot.');
    return null;
  }
}

interface LegacyColorPickerSettings {
  swatches?: string[],
  opacity?: boolean
}


class LegacyColorPicker {
  /**
   * Initialize the color picker for the given element
   */
  public initialize(element: HTMLInputElement, options: LegacyColorPickerSettings = {}): void {
    if (element.parentElement instanceof Typo3BackendColorPicker) {
      return;
    }

    const colorPicker = document.createElement('typo3-backend-color-picker');
    colorPicker.swatches = options.swatches?.join(';') ?? '';
    colorPicker.opacity = options.opacity ?? false;
    element.parentNode.insertBefore(colorPicker, element);
    colorPicker.appendChild(element);
  }
}

export default new LegacyColorPicker();

// Register the custom element
declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-color-picker': Typo3BackendColorPicker;
  }
}
