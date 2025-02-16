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

import { html, LitElement, type TemplateResult, nothing } from 'lit';
import { customElement, property, state } from 'lit/decorators';
import { until } from 'lit/directives/until.js';
import '@typo3/backend/element/spinner-element';
import '@typo3/backend/element/icon-element';
import { copyToClipboard } from '@typo3/backend/copy-to-clipboard';
import Notification from '@typo3/backend/notification';
import { lll } from '@typo3/core/lit-helper';
import { markdown } from '@typo3/core/directive/markdown';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { BaseElement } from '@typo3/backend/settings/type/base';

type ValueType = string|number|boolean|string[]|null;

/** @see \TYPO3\CMS\Core\Settings\SettingDefinition */
interface SettingDefinition {
  key: string,
  type: string,
  default: ValueType,
  label: string,
  description: string|null,
  readonly: boolean,
  // @todo php json_encode encodes ['0' => 'foo'] as ['foo'] instead of {'0' => 'foo'}
  enum: Record<string, string>|Array<string>,
  categories: string[],
  tags: string[],
}

/** @see \TYPO3\CMS\Backend\Dto\Settings\EditableSetting */
interface EditableSetting {
  definition: SettingDefinition,
  value: ValueType,
  systemDefault: ValueType,
  status: string,
  warnings: string[],
  typeImplementation: string,
}

@customElement('typo3-backend-editable-setting')
export class EditableSettingElement extends LitElement {

  @property({ type: Object }) setting: EditableSetting;
  @property({ type: String }) dumpuri: string;
  @property({ type: Boolean }) debug: boolean = false;

  @state()
  hasChange: boolean = false;

  typeElement: BaseElement<unknown> = null;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    const { value, systemDefault, definition } = this.setting;
    return html`
      <div
        class=${`settings-item settings-item-${definition.type} ${this.hasChange ? 'has-change' : ''}`}
        tabindex="0"
        data-status=${JSON.stringify(value) === JSON.stringify(systemDefault) ? 'none' : 'modified'}
      >
        <!-- data-status=modified|error|none-->
        <div class="settings-item-indicator"></div>
        <div class="settings-item-title">
          <label for=${`setting-${definition.key}`} class="settings-item-label">${definition.label}</label>
          <div class="settings-item-description">${markdown(definition.description ?? '', 'minimal')}</div>
          ${this.debug ? html`<div class="settings-item-key">${definition.key}</div>` : nothing}
        </div>
        <div class="settings-item-control">
          ${until(this.renderField(), html`<typo3-backend-spinner></typo3-backend-spinner>`)}
        </div>
        <div class="settings-item-message"></div>
        <div class="settings-item-actions">
          ${this.renderActions()}
        </div>
      </div>
    `;
  }

  protected async renderField(): Promise<HTMLElement> {
    const { definition, value, typeImplementation } = this.setting;
    let element = this.typeElement
    if (!element) {
      const implementation = await import(typeImplementation);
      if (!('componentName' in implementation)) {
        throw new Error(`module ${typeImplementation} is missing the "componentName" export`);
      }
      element = document.createElement(implementation.componentName);
      this.typeElement = element;

      element.addEventListener('typo3:setting:changed', (e: CustomEvent) => {
        this.hasChange = JSON.stringify(this.setting.value) !== JSON.stringify(e.detail.value);
      });
    }

    // Force conversion to an object, as PHP json_encode encodes ['0' => 'foo'] as
    // ['foo'] instead of {'0' => 'foo'}
    const enumEntries = Object.entries(definition.enum || {});

    const attributes = {
      key: definition.key,
      formid: `setting-${definition.key}`,
      name: `settings[${definition.key}]`,
      value: Array.isArray(value) ? JSON.stringify(value) : String(value),
      debug: this.debug,
      readonly: definition.readonly,
      enum: enumEntries.length > 0 ? JSON.stringify(Object.fromEntries(enumEntries)) : false,
      default: Array.isArray(definition.default) ? JSON.stringify(definition.default) : String(definition.default),
    };
    for (const [key, value] of Object.entries(attributes)) {
      if (typeof value === 'boolean') {
        if (value && !element.hasAttribute(key)) {
          element.setAttribute(key, '');
        }
        if (!value && element.hasAttribute(key)) {
          element.removeAttribute(key);
        }
        continue;
      }
      if (element.getAttribute(key) !== value) {
        element.setAttribute(key, value);
      }
    }

    return element;
  }

  protected renderActions(): TemplateResult {
    const { definition } = this.setting;
    return html`
      <div class="dropdown">
        <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          <typo3-backend-icon identifier="actions-cog" size="small"></typo3-backend-icon>
          <span class="visually-hidden">More actions</span>
        </button>
        <ul class="dropdown-menu">
          <li>
            <button class="dropdown-item dropdown-item-spaced"
              type="button"
              ?disabled=${definition.readonly}
              @click="${() => this.setToDefaultValue()}">
              <typo3-backend-icon identifier="actions-undo" size="small"></typo3-backend-icon> ${lll('edit.resetSetting')}
            </button>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <typo3-copy-to-clipboard
              text=${definition.key}
              class="dropdown-item dropdown-item-spaced"
            >
              <typo3-backend-icon identifier="actions-clipboard" size="small"></typo3-backend-icon> ${lll('edit.copySettingsIdentifier')}
            </typo3-copy-to-clipboard>
          </li>
          ${this.dumpuri ? html`
            <li>
              <button class="dropdown-item dropdown-item-spaced"
                type="button"
                @click="${() => this.copyAsYaml()}">
                <typo3-backend-icon identifier="actions-clipboard-paste" size="small"></typo3-backend-icon> ${lll('edit.copyAsYaml')}

              </a>
            </li>
          ` : nothing}
        </ul>
      </div>
    `
  }

  protected setToDefaultValue(): void {
    if (this.typeElement) {
      this.typeElement.value = this.setting.systemDefault as unknown;
    }
  }

  protected async copyAsYaml(): Promise<void> {
    const formData = new FormData(this.typeElement.form);
    const name = `settings[${this.setting.definition.key}]`
    const value = formData.get(name);

    const data = new FormData();
    data.append('specificSetting', this.setting.definition.key);
    data.append(name, value);

    // @todo hookup with NProgress
    const response = await new AjaxRequest(this.dumpuri).post(
      data
    );

    const result = await response.resolve();

    if (typeof result.yaml === 'string') {
      copyToClipboard(result.yaml);
    } else {
      console.warn('Value can not be copied to clipboard.', typeof result.yaml);
      Notification.error(lll('copyToClipboard.error'));
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-editable-setting': EditableSettingElement;
  }
}
