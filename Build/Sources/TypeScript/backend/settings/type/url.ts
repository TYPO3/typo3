import { html, nothing, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators.js';
import { live } from 'lit/directives/live';
import { BaseElement } from './base';

export const componentName = 'typo3-backend-settings-type-url';

@customElement(componentName)
export class UrlTypeElement extends BaseElement<
  string,
  {
    pattern?: string,
  }
> {
  @property({ type: String }) override value: string = '';

  protected handleChange(e: InputEvent): void {
    const input = e.target as HTMLInputElement;
    if (input.reportValidity()) {
      this.value = input.value.trim();
    }
  }

  protected override render(): TemplateResult {
    return html`
      <input
        type="url"
        id=${this.formid}
        class="form-control"
        .value=${live(this.value)}
        ?readonly=${this.readonly}
        pattern=${this.options.pattern ?? nothing}
        @change=${this.handleChange}
      />
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-settings-type-url': UrlTypeElement;
  }
}
