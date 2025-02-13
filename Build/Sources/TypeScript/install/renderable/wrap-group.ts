import { LitElement, TemplateResult, html } from 'lit';
import { customElement, property } from 'lit/decorators';

@customElement('typo3-install-wrap-group')
class WrapGroupElement extends LitElement {
  @property({ type: String }) wrapId: string | null = null;
  @property({ type: Array }) values: string[] | null = null;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <div class="form-multigroup-wrap">
        <div class="form-multigroup-item">
          <div class="input-group">
            <input id="${this.wrapId}_wrap_start" class="form-control t3js-emconf-wrapfield" data-target="#${this.wrapId}" value="${this.values[0].trim()}"/>
          </div>
        </div>
        <div class="form-multigroup-item">
          <div class="input-group">
            <input id="${this.wrapId}_wrap_end" class="form-control t3js-emconf-wrapfield" data-target="#${this.wrapId}" value="${this.values[0].trim()}"/>
          </div>
        </div>
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-install-wrap-group': WrapGroupElement;
  }
}
