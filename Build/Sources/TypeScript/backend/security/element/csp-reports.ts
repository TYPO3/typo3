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
import { html, LitElement, nothing, TemplateResult } from 'lit';
import { classMap } from 'lit/directives/class-map';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { styleTag, lll } from '@typo3/core/lit-helper';

enum CspReportAttribute {
  fixable= 'fixable',
  irrelevant = 'irrelevant',
  suspicious = 'suspicious',
}

interface CspReportUuids {
  uuids: string[]
}

interface SummarizedCspReport {
  uuid: string,
  scope: string;
  created: Date;
  requestTime: number;
  summary: string;
  count: number,
  attributes: CspReportAttribute[],
  mutationHashes: string[];
  details: {
    // https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP#violation_report_syntax
    disposition: 'enforce' | 'report',

    blockedUri: string,
    documentUri: string,
    statusCode: number,
    referrer?: string,

    originalPolicy: string,
    effectiveDirective: string,
    /**
     * @deprecated historical alias of effectiveDirective
     * see https://w3c.github.io/webappsec-csp/#violation-events
     */
    violatedDirective: string,

    scriptSample?: string,
    columnNumber?: number,
    lineNumber?: number,
  };
}

interface MutationSuggestion {
  collection: MutationCollection;
  identifier: string;
  priority?: number;
  label?: string;
  hash: string;
  hmac: string;
}

interface MutationCollection {
  mutations: Mutation[];
}

interface Mutation {
  directive: string;
  mode: string,
  sources: string[]
}

@customElement('typo3-backend-security-csp-reports')
export class CspReports extends LitElement {
  @property({ type: Array }) scopes: string[];
  @property({ type: String }) controlUri: string;
  @state() selectedScope: string = null;

  @state() reports: SummarizedCspReport[] = [];
  @state() selectedReport: SummarizedCspReport | null = null;
  @state() suggestions: MutationSuggestion[] = [];

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  connectedCallback() {
    super.connectedCallback();
    this.fetchReports();
  }

  public render(): TemplateResult {
    return html`
      ${styleTag`
        .infolist-container {
          container-type: inline-size;
        }
        .infolist {
          display: flex;
          gap: var(--typo3-spacing);
          flex-direction: column;
        }
        .infolist-info {
          display: none;
        }
        .infolist-overlay {
          position: relative;
          z-index: 10;
        }

        @container (max-width: 899px) {
          .infolist-info-showrecord {
            display: block;
            background: rgba(0,0,0,.5);
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
          }
        }

        @container (min-width: 900px) {
          .infolist {
            display: grid;
            grid-template:
              "header header"
              "content info";
            grid-template-columns: auto 400px;
          }
          .infolist-header {
            grid-area: header;
          }
          .infolist-content {
            grid-area: content;
          }
          .infolist-info {
            display: block;
            grid-area: info;
          }
          .infolist-info-norecord,
          .infolist-info-record {
            position: sticky;
            top: calc(var(--module-docheader-height) + var(--typo3-spacing));
          }
        }
      `}
      <div class="infolist-container infolist-overlay">
        <div class="infolist">
          <div class="infolist-header">
            ${this.renderNavigation()}
          </div>
        </div>
      </div>

      <div class="infolist-container">
        <div class="infolist">
          <div class="infolist-content">
            <div class="table-fit mb-0">
              <table class="table table-striped">
                <thead>
                <tr>
                  <th>${lll('label.created') || 'Created'}</th>
                  <th>${lll('label.scope') || 'Scope'}</th>
                  <th>${lll('label.violation') || 'Violation'}</th>
                  <th>${lll('label.uri') || 'URI'}</th>
                  <th></th>
                </tr>
                </thead>
                <tbody>
                ${this.reports.length === 0 ? html`
                  <tr><td colspan="5">${lll('label.label.noEntriesAvailable') || 'No entries available.'}</td></tr>
                ` : nothing}
                ${this.reports.map((report: SummarizedCspReport) => html`
                  <tr class=${classMap({ 'table-info': this.selectedReport === report })} data-mutation-group=${report.mutationHashes.join('-')}
                      @click=${() => this.selectReport(report)}>
                    <td>${report.created}</td>
                    <td>${report.scope}</td>
                    <td>
                      <span class="badge bg-warning">${report.count}</span>
                      ${report.details.violatedDirective}
                    </td>
                    <td>${this.shortenUri(report.details.blockedUri)}</td>
                    <td>${report.attributes.join(', ')}</td>
                  </tr>
                `)}
                </tbody>
              </table>
            </div>
          </div>
          <div class="infolist-info${this.selectedReport ? ' infolist-info-showrecord' : ''}">
            ${this.renderGuide()}
            ${this.renderSelectedReport()}
          </div>
        </div>
      </div>
    `;
  }

  protected renderNavigation(): TemplateResult {
    return html`
      <div class="btn-group">
        <button type="button" class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="${lll('scope')}}">
          ${null === this.selectedScope ? lll('all') || 'ALL' : this.selectedScope}
        </button>
        <ul class="dropdown-menu">
          <button class="dropdown-item dropdown-item-spaced" title="${lll('all') || 'ALL'}" @click="${() => { this.selectScope(null); }}">
            <span class="${null === this.selectedScope ? 'text-primary' : '' }">
              <typo3-backend-icon identifier="${null === this.selectedScope ? 'actions-dot' : 'empty-empty'}" size="small"></typo3-backend-icon>
            </span>
            ${lll('all') || 'ALL'}
          </button>
          ${this.scopes.map((scope: string) => html`
            <li>
              <button class="dropdown-item dropdown-item-spaced" title="${scope}" @click="${() => { this.selectScope(scope); }}">
                <span class="${scope === this.selectedScope ? 'text-primary' : '' }">
                  <typo3-backend-icon identifier="${scope === this.selectedScope ? 'actions-dot' : 'empty-empty'}" size="small"></typo3-backend-icon>
                </span>
                ${scope}
              </button>
            </li>`)}
        </ul>
      </div>`;
  }

  protected renderGuide(): TemplateResult {
    return html`${!this.selectedReport ? html`
      <div class="infolist-info-norecord">
        <div class="card mb-0">
          <div class="card-body">
            <p>${ lll('label.guide.no_record_selected') || 'Select a row to see more information.'}</p>
          </div>
        </div>
      </div>
    ` : nothing }`;
  }

  protected renderSelectedReport(): TemplateResult {
    const report = this.selectedReport;
    return html`${report ? html`
      <div class="infolist-info-record">
        <div class="card mb-0">
          <div class="card-header">
            <h3>${ lll('label.details') || 'Details'}</h3>
          </div>
          <div class="card-body">
            <dl>
              <dt>${ lll('label.directive') || 'Directive'} / ${ lll('label.disposition') || 'Disposition'}</dt>
              <dd>${report.details.effectiveDirective} / ${report.details.disposition}</dd>

              <dt>${ lll('label.blocked_uri') || 'Blocked URI'}</dt>
              <dd>${report.details.blockedUri}</dd>

              <dt>${ lll('label.document_uri') || 'Document URI'}</dt>
              <dd>
                ${report.details.documentUri}
                ${report.details.lineNumber ? html`
                  (${report.details.lineNumber}:${report.details.columnNumber})
                ` : nothing}</dd>

              ${report.details.scriptSample ? html`
                <dt>${ lll('label.sample') || 'Sample'}</dt>
                <dd><code>
                  <pre>${report.details.scriptSample}</pre>
                </code></dd>
              ` : nothing}

              <dt>${ lll('label.uuid') || 'UUID'}</dt>
              <dd><code>${report.uuid}</code></dd>

              <dt>${ lll('label.summary') || 'Summary'}</dt>
              <dd><code>${report.summary}</code></dd>
            </dl>
          </div>
          ${this.suggestions.length > 0 ? html`
            <div class="card-header">
              <h3>${ lll('label.suggestions') || 'Suggestions'}</h3>
            </div>
          ` : nothing}
          ${this.suggestions.map((suggestion: MutationSuggestion) => html`
            <div class="card-body">
              <h4>${suggestion.label || suggestion.identifier}</h4>
              ${suggestion.collection.mutations.map((mutation: Mutation) => html`
                <p>
                  <i>${mutation.mode}</i>
                  <code>${mutation.directive}: ${mutation.sources.join(' ')}</code>
                </p>
              `)}
              <button class="btn btn-primary" @click=${() => this.invokeMutateReportAction(report, suggestion)}>
                <typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon>
                ${ lll('button.apply') || 'Apply'}
              </button>
            </div>
          `)}

          <div class="card-footer">
            <button class="btn btn-default" @click=${() => this.selectReport(null)}>
              <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
              ${ lll('button.close') || 'Close'}
            </button>
            <button class="btn btn-default" @click=${() => this.invokeMuteReportAction(report)}>
              <typo3-backend-icon identifier="actions-ban" size="small"></typo3-backend-icon>
              ${ lll('button.mute') || 'Mute'}
            </button>
            <button class="btn btn-default" @click=${() => this.invokeDeleteReportAction(report)}>
              <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
              ${ lll('button.delete') || 'Delete'}
            </button>
          </div>
        </div>
      </div>
    ` : nothing }`;
  }

  private selectReport(report: SummarizedCspReport): void {
    this.suggestions = [];
    if (report !== null && this.selectedReport !== report) {
      this.selectedReport = report;
      this.invokeHandleReportAction(report)
        .then((suggestions: MutationSuggestion[]) => this.suggestions = suggestions);
    } else {
      this.selectedReport = null;
    }
  }

  private selectScope(scope: string): void {
    this.selectedScope = scope;
    this.fetchReports();
  }

  private fetchReports(): void {
    this.invokeFetchReportsAction().then((reports: SummarizedCspReport[]) => this.reports = reports);
  }

  /*
   * Remote API calls
   */

  private filterReports(...uuids: string[]): void {
    if (uuids.includes(this.selectedReport?.uuid)) {
      this.selectedReport = null;
    }
    this.reports = this.reports.filter((report: SummarizedCspReport) => !uuids.includes(report.uuid));
  }

  private invokeFetchReportsAction(): Promise<SummarizedCspReport[]> {
    return (new AjaxRequest(this.controlUri))
      .post({ action: 'fetchReports', scope: this.selectedScope || '' })
      .then((response: AjaxResponse) => response.resolve('application/json'));
  }

  private invokeHandleReportAction(report: SummarizedCspReport) {
    return (new AjaxRequest(this.controlUri))
      .post({ action: 'handleReport', uuid: report.uuid })
      .then((response: AjaxResponse) => response.resolve('application/json'));
  }

  private invokeMutateReportAction(report: SummarizedCspReport, suggestion: MutationSuggestion) {
    const summaries = this.reports
      .filter((other: SummarizedCspReport) => other.mutationHashes.includes(suggestion.hash))
      .map((other: SummarizedCspReport) => other.summary);
    return (new AjaxRequest(this.controlUri))
      .post({ action: 'mutateReport', scope: report.scope, hmac: suggestion.hmac, suggestion, summaries })
      .then((response: AjaxResponse) => response.resolve('application/json'))
      .then((response: CspReportUuids) => this.filterReports(...response.uuids));
  }

  private invokeMuteReportAction(report: SummarizedCspReport): void {
    (new AjaxRequest(this.controlUri))
      .post({ action: 'muteReport', summary: report.summary })
      .then((response: AjaxResponse) => response.resolve('application/json'))
      .then((response: CspReportUuids) => this.filterReports(...response.uuids));
  }

  private invokeDeleteReportAction(report: SummarizedCspReport): void {
    (new AjaxRequest(this.controlUri))
      .post({ action: 'deleteReport', summary: report.summary })
      .then((response: AjaxResponse) => response.resolve('application/json'))
      .then((response: CspReportUuids) => this.filterReports(...response.uuids));
  }

  /*
   * Helper methods
   */

  private shortenUri(value: string): string
  {
    if (value === 'inline') {
      return value;
    }
    try {
      const uri = new URL(value);
      return uri.hostname;
    } catch (err) {
      return value;
    }
  }
}
