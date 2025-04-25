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
import { lll } from '@typo3/core/lit-helper';

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
  meta: {
    addr: string,
    agent: string,
  };
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

    sourceFile?: string,
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

  connectedCallback() {
    super.connectedCallback();
    this.fetchReports();
  }

  protected createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected render(): TemplateResult {
    return html`
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
                      ${report.details.effectiveDirective}
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
        <button type="button" class="btn btn-default dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="${lll('label.scope') || 'Scope'}">
          ${null === this.selectedScope ? lll('label.all') || 'ALL' : this.selectedScope}
        </button>
        <ul class="dropdown-menu">
          <button class="dropdown-item dropdown-item-spaced" title="${lll('label.all') || 'ALL'}" @click=${() => this.selectScope(null)}>
            <span class="${null === this.selectedScope ? 'text-primary' : '' }">
              <typo3-backend-icon identifier="${null === this.selectedScope ? 'actions-dot' : 'empty-empty'}" size="small"></typo3-backend-icon>
            </span>
            ${lll('label.all') || 'ALL'}
          </button>
          ${this.scopes.map((scope: string) => html`
            <li>
              <button class="dropdown-item dropdown-item-spaced" title="${scope}" @click=${() => this.selectScope(scope)}>
                <span class="${scope === this.selectedScope ? 'text-primary' : '' }">
                  <typo3-backend-icon identifier="${scope === this.selectedScope ? 'actions-dot' : 'empty-empty'}" size="small"></typo3-backend-icon>
                </span>
                ${scope}
              </button>
            </li>`)}
        </ul>
        <button type="button" class="btn btn-danger mx-3" title="${lll('label.removeAll') || 'Remove all'}" @click=${() => this.invokeDeleteReportsAction()}>
          ${lll('label.removeAll') || 'Remove all'}
          ${this.selectedScope !== null ? html`"${this.selectedScope}"` : nothing}
        </button>
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

              <dt>${ lll('label.document_uri') || 'Document URI'}</dt>
              <dd>${report.details.documentUri} ${this.renderCodeLocation(report)}</dd>

              ${report.details.sourceFile && report.details.sourceFile !== report.details.documentUri ? html`
                <dt>${ lll('label.source_file') || 'Source File'}</dt>
                <dd>${report.details.sourceFile}</dd>
              ` : nothing}

              <dt>${ lll('label.blocked_uri') || 'Blocked URI'}</dt>
              <dd>${report.details.blockedUri}</dd>

              ${report.details.scriptSample ? html`
                <dt>${ lll('label.sample') || 'Sample'}</dt>
                <dd><code>${report.details.scriptSample}</code></dd>
              ` : nothing}

              ${report.meta.agent ? html`
                <dt>${ lll('label.user_agent') || 'User Agent'}</dt>
                <dd><code>${report.meta.agent}</code></dd>
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

  private renderCodeLocation(report: SummarizedCspReport): TemplateResult|symbol {
    if (!report.details.lineNumber) {
      return nothing;
    }
    const parts = [report.details.lineNumber];
    if (report.details.columnNumber) {
      parts.push(report.details.columnNumber);
    }
    return html`(${parts.join(':')})`;
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
      .post({ action: 'muteReport', summaries: [report.summary] })
      .then((response: AjaxResponse) => response.resolve('application/json'))
      .then((response: CspReportUuids) => this.filterReports(...response.uuids));
  }

  private invokeDeleteReportAction(report: SummarizedCspReport): void {
    (new AjaxRequest(this.controlUri))
      .post({ action: 'deleteReport', summaries: [report.summary] })
      .then((response: AjaxResponse) => response.resolve('application/json'))
      .then((response: CspReportUuids) => this.filterReports(...response.uuids));
  }

  private invokeDeleteReportsAction(): void {
    (new AjaxRequest(this.controlUri))
      .post({ action: 'deleteReports', scope: this.selectedScope || '' })
      .then((response: AjaxResponse) => response.resolve('application/json'))
      .then(() => this.fetchReports())
      .then(() => this.selectReport(null));
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
    } catch {
      return value;
    }
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-security-csp-reports': CspReports;
  }
}
