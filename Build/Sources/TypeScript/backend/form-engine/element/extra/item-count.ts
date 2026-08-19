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

import { customElement } from 'lit/decorators.js';
import { html, LitElement, nothing, type TemplateResult } from 'lit';
import Utility from '@typo3/backend/utility';
import { selector } from '@typo3/core/literals';
import labels from '~labels/core.core';

export interface ItemCountLimits {
  minItems: number;
  maxItems: number;
  type: string;
}

const UNLIMITED_MAX_ITEMS = 99999;

/**
 * Reads the configured item count limits (minitems/maxitems) of a field that
 * carries a `data-formengine-validation-rules` attribute. Used both by the
 * web component and by FormEngine to decide whether a badge is required.
 */
export function parseItemCountLimits(field: HTMLElement): ItemCountLimits {
  let minItems = 0;
  let maxItems = Number(field.dataset.maxitems ?? 0);
  let type = '';

  const validationRules = field.dataset.formengineValidationRules ?? '';
  if (validationRules !== '') {
    try {
      const rules = JSON.parse(validationRules) as Array<{ type?: string, minItems?: number, maxItems?: number }>;
      const itemRule = rules.find((rule): boolean => typeof rule.minItems !== 'undefined' || typeof rule.maxItems !== 'undefined');
      if (itemRule) {
        type = itemRule.type ?? '';
        if (typeof itemRule.minItems !== 'undefined') {
          minItems = Number(itemRule.minItems);
        }
        if (typeof itemRule.maxItems !== 'undefined') {
          maxItems = Number(itemRule.maxItems);
        }
      }
    } catch {
      // Ignore malformed JSON and fall back to available data attributes.
    }
  }

  if (!Number.isFinite(minItems) || minItems < 0) {
    minItems = 0;
  }
  if (!Number.isFinite(maxItems) || maxItems < 0) {
    maxItems = 0;
  }
  if (maxItems >= UNLIMITED_MAX_ITEMS) {
    maxItems = 0;
  }

  return { minItems, maxItems, type };
}

/**
 * Displays a compact badge with the current selection count next to any field
 * that has minitems/maxitems item count validation rules. It detects the field
 * it is attached to and reacts to changes, so it works for the built-in field
 * types as well as any custom field emitting `data-formengine-validation-rules`.
 */
@customElement('typo3-backend-formengine-item-count')
export class ItemCount extends LitElement {
  public field: HTMLElement = null;

  private form: HTMLFormElement = null;

  public override connectedCallback(): void {
    super.connectedCallback();
    this.form = this.field?.closest('form') ?? this.closest('form');
    this.form?.addEventListener('change', this.onChange);
  }

  public override disconnectedCallback(): void {
    this.form?.removeEventListener('change', this.onChange);
    super.disconnectedCallback();
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult | typeof nothing {
    if (this.field === null || this.isSingleSelectField(this.field)) {
      return nothing;
    }

    const { minItems, maxItems, type } = parseItemCountLimits(this.field);
    if (minItems <= 0 && maxItems <= 0) {
      return nothing;
    }

    const selectedCount = this.getSelectedItemCount(this.field, type);
    const belowMin = minItems > 0 && selectedCount < minItems;
    const limitExceeded = maxItems > 0 && selectedCount > maxItems;
    const isInvalid = belowMin || limitExceeded;

    let label: string;
    if (minItems > 0 && maxItems > 0) {
      label = labels.get('labels.selectedItems.short.withMinAndMax', {
        actualItems: selectedCount.toString(),
        maxItems: maxItems.toString(),
        minItems: minItems.toString(),
      });
    } else if (maxItems > 0) {
      label = labels.get('labels.selectedItems.short.withMax', {
        actualItems: selectedCount.toString(),
        maxItems: maxItems.toString(),
      });
    } else {
      label = labels.get('labels.selectedItems.short.withMin', {
        actualItems: selectedCount.toString(),
        minItems: minItems.toString(),
      });
    }

    const fieldLabel = this.resolveFieldLabel();
    if (fieldLabel !== '') {
      label += ' ' + fieldLabel;
    }

    return html`
      <small class="form-text">
        <span class="badge ${isInvalid ? 'badge-danger' : 'badge-info'}" title="${label}">${label}</span>
      </small>
    `;
  }

  private readonly onChange = (): void => {
    this.requestUpdate();
  };

  private isSingleSelectField(field: HTMLElement): boolean {
    return field instanceof HTMLSelectElement && !field.multiple && field.size <= 1;
  }

  private resolveFieldLabel(): string {
    const fieldContainer = this.closest('.form-group')
      ?? this.field?.closest('.form-group')
      ?? this.field?.closest('fieldset');
    const labelElement = fieldContainer?.querySelector('.t3js-formengine-label') as HTMLElement | null;
    if (!labelElement) {
      return '';
    }

    const labelClone = labelElement.cloneNode(true) as HTMLElement;
    labelClone.querySelectorAll('code').forEach((codeEl: Element): void => codeEl.remove());
    return (labelClone.textContent ?? '').trim();
  }

  private getSelectedItemCount(field: HTMLElement, type: string): number {
    const relatedFieldName = field.dataset.relatedfieldname ?? '';
    const relatedField = relatedFieldName !== '' && this.form !== null
      ? this.form.querySelector(selector`[name="${relatedFieldName}"]`) as HTMLInputElement | HTMLSelectElement | null
      : null;

    switch (type) {
      case 'select':
      case 'category':
        if (relatedField !== null) {
          return Utility.trimExplode(',', relatedField.value).length;
        }
        if (field instanceof HTMLSelectElement) {
          return field.querySelectorAll('option:checked').length;
        }
        return field.querySelectorAll('input[value]:checked').length;
      case 'range':
        if (relatedField !== null) {
          return Utility.trimExplode(',', relatedField.value).length;
        }
        return parseInt((field as HTMLInputElement).value, 10) || 0;
      case 'group':
      case 'folder':
      case 'inline':
      default:
        return Utility.trimExplode(',', (field as HTMLInputElement).value ?? '').length;
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-formengine-item-count': ItemCount;
  }
}
