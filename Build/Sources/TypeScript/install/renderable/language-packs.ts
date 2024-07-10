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

import { customElement, property, state } from 'lit/decorators';
import { LitElement, TemplateResult, html, nothing } from 'lit';
import { classMap } from 'lit/directives/class-map';

type Language = {
  iso: string,
  name: string,
  active: boolean,
  lastUpdate: string,
  dependencies: string[]
}

type ExtensionPack = {
  iso: string,
  exists: boolean,
  lastUpdate: string
}

type Extension = {
  key: string,
  title: string,
  type: string,
  icon: string,
  packs: ExtensionPack[]
}

export type LanguagePacksGetDataResponse = {
  languages: Language[],
  extensions: Extension[],
  activeLanguages: string[],
  activeExtensions: string[],
}

export type ActivateLanguageEvent = {
  iso: string,
}
export type DeactivateLanguageEvent = {
  iso: string,
}
export type DownloadPacksEvent = {
  iso?: string,
  extension?: string
}

@customElement('typo3-install-language-matrix')
export class LanguageMatrixElement extends LitElement {
  @property({ type: Boolean }) configurationIsWritable: boolean = false;
  @property({ type: Object }) data: LanguagePacksGetDataResponse | null = null;

  @state()
  private addLanguagesActive: boolean = false;

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  public render(): TemplateResult {
    return html`
      <div>
        <h3>Active languages</h3>
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>
                <div class="btn-group">
                  ${this.globalActions()}
                </div>
              </th>
              <th>Locale</th>
              <th>Dependencies</th>
              <th>Last update</th>
            </tr>
          </thead>
          <tbody>
            ${this.renderLanguages()}
          </tbody>
        </table>
      </div>
    `;
  }

  private globalActions(): TemplateResult {
    const updateButtonClasses = {
      'btn': true,
      'btn-default': true,
      'update-all': true,
      'disabled': !this.hasActiveLanguages()
    }

    return html`
      ${this.configurationIsWritable ? html`
        <button class="btn btn-default t3js-languagePacks-addLanguage-toggle"
          @click=${() => this.addLanguagesActive = !this.addLanguagesActive}>
          <typo3-backend-icon identifier=${this.addLanguagesActive ? 'actions-minus' : 'actions-plus'} size="small"></typo3-backend-icon>
          Add language
        </button>
      ` : nothing}
      <button class=${classMap(updateButtonClasses)} ?disabled=${!this.hasActiveLanguages()}
        @click=${() => this.dispatchEvent(new CustomEvent<DownloadPacksEvent>('download-packs'))}>
        <typo3-backend-icon identifier="actions-download" size="small"></typo3-backend-icon>
        Update all
      </button>
    `;
  }

  private renderLanguageActions(language: Language): TemplateResult[] {
    const actions: TemplateResult[] = [];
    const { iso } = language;
    const eventData = { detail: { iso } }

    if (language.active) {
      if (this.configurationIsWritable) {
        actions.push(html`
          <button class="btn btn-default" title="Deactivate"
            @click=${() => this.dispatchEvent(new CustomEvent<DeactivateLanguageEvent>('deactivate-language', eventData))}>
            <typo3-backend-icon identifier="actions-minus" size="small"></typo3-backend-icon>
          </button>
        `);
      }

      actions.push(html`
        <button class="btn btn-default" title="Download language packs"
          @click=${() => this.dispatchEvent(new CustomEvent<DownloadPacksEvent>('download-packs', eventData))}>
          <typo3-backend-icon identifier="actions-download" size="small"></typo3-backend-icon>
        </button>
      `);
    } else {
      if (this.configurationIsWritable) {
        actions.push(html`
          <button class="btn btn-default" title="Activate"
            @click=${() => this.dispatchEvent(new CustomEvent<ActivateLanguageEvent>('activate-language', eventData))}>
            <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
          </button>
        `);
      }
    }

    return actions;
  }

  private renderLanguages(): TemplateResult[] {
    return this.data.languages
      .filter(language => this.addLanguagesActive ? !language.active : language.active)
      .map(language => html`
        <tr class=${classMap({ 't3-languagePacks-inactive': !language.active })}>
          <td>
            <div class="btn-group">
              ${this.renderLanguageActions(language)}
            </div>
            ${language.name}
          </td>
          <td>${language.iso}</td>
          <td>${language.dependencies.join(', ')}</td>
          <td>${language.lastUpdate === null ? '' : language.lastUpdate}</td>
        </tr>
      `);
  }

  private hasActiveLanguages() {
    return Array.isArray(this.data.activeLanguages) && this.data.activeLanguages.length;
  }
}

@customElement('typo3-install-extension-matrix')
export class ExtensionMatrixElement extends LitElement {
  @property({ type: Object }) data: LanguagePacksGetDataResponse | null = null;

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // @todo Switch to Shadow DOM once Bootstrap CSS style can be applied correctly
    return this;
  }

  public render(): TemplateResult {
    if (this.data.extensions.length === 0) {
      return html`
        <typo3-install-infobox
          severity="0"
          subject="Language packs have been found for every installed extension."
          content="To download the latest changes, use the refresh button in the list above.">
        </typo3-install-infobox>
      `
    }

    return html`
      <div>
        <h3>Translation status</h3>
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>Extension</th>
              <th>Key</th>
              ${this.headerActions()}
          </thead>
          <tbody>
            ${this.renderExtensions()}
          </tbody>
        </table>
      </div>
    `;
  }

  private headerActions(): TemplateResult[] {
    return this.data.activeLanguages.map(activeLanguage => html`
      <th>
        <button class="btn btn-default" title="Download and update all language packs"
          @click=${() => this.dispatchEvent(new CustomEvent<DownloadPacksEvent>('download-packs', { detail: { iso: activeLanguage } }))}>
          <typo3-backend-icon identifier="actions-download" size="small"></typo3-backend-icon>
          ${activeLanguage}
        </button>
      </th>
    `)
  }

  private renderExtensions(): TemplateResult[] {
    return this.data.extensions.map(extension => html`
      <tr>
        <td>
          ${extension.icon ? html`
            <img src="${extension.icon}" alt="${extension.title}" style="max-height: 16px; max-width: 16px;">
          ` : nothing}
          ${extension.title}
        </td>
        <td>${extension.key}</td>
        ${this.renderExtensionActions(extension)}
      </tr>
    `)
  }

  private renderExtensionActions(extension: Extension): TemplateResult[] {
    const cells: TemplateResult[] = [];

    this.data.activeLanguages.forEach((language: string): void => {
      let cell: TemplateResult | typeof nothing = nothing;

      extension.packs.forEach((pack: ExtensionPack): void => {
        if (pack.iso !== language) {
          return;
        }

        let tooltip;
        if (pack.exists !== true) {
          if (pack.lastUpdate !== null) {
            tooltip = 'No language pack available for ' + pack.iso + ' when tried at ' + pack.lastUpdate + '. Click to re-try.';
          } else {
            tooltip = 'Language pack not downloaded. Click to download';
          }
        } else if (pack.lastUpdate === null) {
          tooltip = 'Downloaded. Click to renew';
        } else {
          tooltip = 'Language pack downloaded at ' + pack.lastUpdate + '. Click to renew';
        }

        const eventData = {
          detail: {
            iso: pack.iso,
            extension: extension.key
          }
        };
        cell = html`
          <td>
            <button class="btn btn-default" title=${tooltip}
              @click=${() => this.dispatchEvent(new CustomEvent<DownloadPacksEvent>('download-packs', eventData))}>
              <typo3-backend-icon identifier="actions-download" size="small"></typo3-backend-icon>
            </button>
          </td>
        `;
      });

      // Render empty colum to avoid disturbed table build up if pack was not found for language.
      if (cell === nothing) {
        cell = html`<td></td>`;
      }

      cells.push(cell);
    });

    return cells;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-install-language-matrix': LanguageMatrixElement;
    'typo3-install-extension-matrix': ExtensionMatrixElement;
  }
}
