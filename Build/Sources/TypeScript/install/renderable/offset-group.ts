import { LitElement, type TemplateResult, html } from 'lit';
import { customElement, property } from 'lit/decorators';

@customElement('typo3-install-offset-group')
class OffsetGroupElement extends LitElement {
  @property({ type: String }) offsetId: string | null = null;
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
            <div class="input-group-text">x</div>
            <input id="${this.offsetId}_offset_x" class="form-control t3js-emconf-offsetfield" data-target="#${this.offsetId}" value="${this.values[0]?.trim()}"/>
          </div>
        </div>
        <div class="form-multigroup-item">
          <div class="input-group">
            <div class="input-group-text">y</div>
            <input id="${this.offsetId}_offset_y" class="form-control t3js-emconf-offsetfield" data-target="#${this.offsetId}" value="${this.values[1]?.trim()}"/>
          </div>
        </div>
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-install-offset-group': OffsetGroupElement;
  }
}
