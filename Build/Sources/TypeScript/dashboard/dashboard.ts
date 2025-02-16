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

import { html, LitElement, nothing, type TemplateResult } from 'lit';
import { customElement, property, state, query } from 'lit/decorators';
import { repeat } from 'lit/directives/repeat';
import { unsafeHTML } from 'lit/directives/unsafe-html';
import { styleMap } from 'lit/directives/style-map';
import { Task } from '@lit/task';
import { animate, fadeIn, fadeOut } from '@lit-labs/motion';
import '@typo3/backend/element/icon-element';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import ClientStorage from '@typo3/backend/storage/client';
import { lll, delay } from '@typo3/core/lit-helper';
import Modal, { type ModalElement } from '@typo3/backend/modal';
import { SeverityEnum } from '@typo3/backend/enum/severity';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { Categories, type DataCategoriesInterface, type NewRecordWizardItemSelectedEventInterface } from '@typo3/backend/new-record-wizard';
import { topLevelModuleImport } from '@typo3/backend/utility/top-level-module-import';
import { selector } from '@typo3/core/literals';
import DomHelper from '@typo3/backend/utility/dom-helper';
import Notification from '@typo3/backend/notification';

enum DashboardWidgetMoveIntend {
  start = 'start',
  end = 'end',
  left = 'left',
  right = 'right',
  up = 'up',
  down = 'down',
}

interface DashboardInterface {
  identifier: string,
  title: string,
  widgets: DashboardWidgetConfigurationInterface[]
  widgetPositions: Record<number, DashboardWidgetPosition[]>
}

interface DashboardPresetInterface {
  identifier: string;
  title: string;
  description: string;
  icon: string;
  widgets: Array<string>;
  showInWizard: boolean;
}

interface DashboardWidgetPosition {
  identifier: string,
  height: number,
  width: number,
  y: number,
  x: number,
}

interface DashboardDragInformation {
  identifier: string;
  itemElement: HTMLElement;
  widgetElement: DashboardWidget;
  height: number,
  width: number,
  offsetY: number;
  offsetX: number;
  currentY: number;
  currentX: number;
  initialPositions: DashboardWidgetPosition[];
}

interface DashboardWidgetConfigurationInterface {
  identifier: string,
  type: string,
  height: string,
  width: string,
}

interface DashboardWidgetInterface extends DashboardWidgetConfigurationInterface {
  label: string,
  content: string,
  options: Record<string, unknown>,
  eventdata: Record<string, unknown>,
}

const newRecordWizardEventName = 'typo3:dashboard:widget:add';

export class DashboardWidgetContentRenderedEvent extends Event {
  static readonly eventName = 'typo3:dashboard:widget:content:rendered';

  constructor(
    public readonly widget: DashboardWidgetInterface,
  ) {
    super(DashboardWidgetContentRenderedEvent.eventName, {
      bubbles: true,
      composed: true,
      cancelable: false,
    });
  }
}

class DashboardWidgetMoveIntendEvent extends Event {
  static readonly eventName = 'typo3:dashboard:widget:moveIntend';

  constructor(
    public readonly identifier: string,
    public readonly intend: DashboardWidgetMoveIntend,
  ) {
    super(DashboardWidgetMoveIntendEvent.eventName, {
      bubbles: true,
      composed: true,
      cancelable: false,
    });
  }
}

class DashboardWidgetRemoveEvent extends Event {
  static readonly eventName = 'typo3:dashboard:widget:remove';

  constructor(
    public readonly identifier: string,
  ) {
    super(DashboardWidgetRemoveEvent.eventName, {
      bubbles: true,
      composed: true,
      cancelable: false,
    });
  }
}

class DashboardWidgetRefreshEvent extends Event {
  static readonly eventName = 'typo3:dashboard:widget:refresh';

  constructor(
    public readonly identifier: string
  ) {
    super(DashboardWidgetRefreshEvent.eventName, {
      bubbles: true,
      composed: true,
      cancelable: false,
    });
  }
}

class DashboardAddEvent extends Event {
  static readonly eventName = 'typo3:dashboard:dashboard:add';

  constructor(
    public readonly preset: string,
    public readonly title: string,
  ) {
    super(DashboardAddEvent.eventName);
  }
}

class DashboardEditEvent extends Event {
  static readonly eventName = 'typo3:dashboard:dashboard:edit';

  constructor(
    public readonly identifier: string,
    public readonly title: string,
  ) {
    super(DashboardEditEvent.eventName);
  }
}

class DashboardUpdateEvent extends Event {
  static readonly eventName = 'typo3:dashboard:dashboard:update';

  constructor(
    public readonly identifier: string,
    public readonly widgets: DashboardWidgetConfigurationInterface[],
    public readonly widgetPositions: Record<number, DashboardWidgetPosition[]>,
  ) {
    super(DashboardUpdateEvent.eventName);
  }
}

class DashboardDeleteEvent extends Event {
  static readonly eventName = 'typo3:dashboard:dashboard:delete';

  constructor(
    public readonly identifier: string,
  ) {
    super(DashboardDeleteEvent.eventName);
  }
}

function createSet(item: DashboardWidgetPosition): Set<string> {
  const set = new Set<string>();
  for (let y = 0; y < item.height; y++) {
    for (let x = 0; x < item.width; x++) {
      const cellKey = `${item.y + y}-${item.x + x}`;
      set.add(cellKey);
    }
  }
  return set;
}

@customElement('typo3-dashboard')
export class Dashboard extends LitElement {
  @state() loading: boolean = false;
  @state() dashboards: DashboardInterface[] = [];
  @state() currentDashboard: DashboardInterface | null = null;
  @state() columns: number = 4;
  @state() dragInformation: DashboardDragInformation | null = null
  @query('.dashboard-dragging-container') draggingContainer: HTMLElement;
  private resizeObserver: ResizeObserver | null = null;
  private readonly clientStorageIdentifier: string = 'dashboard/current_dashboard';

  private prefersReducedMotion: boolean = false;
  private mql: MediaQueryList | null = null;

  private dragOverTimeout: number | null = null;

  private activeElementRef: HTMLElement | null = null;

  constructor() {
    super();

    // Refresh Widget
    this.addEventListener(DashboardWidgetRefreshEvent.eventName, (event): void => {
      event.preventDefault();
      const element = this.getGridItemByIdentifier(event.identifier);
      const widgetElement = element.querySelector('typo3-dashboard-widget');
      widgetElement.refresh();
    });

    // Remove Widget
    this.addEventListener(DashboardWidgetRemoveEvent.eventName, (event): void => {
      event.preventDefault();
      const { identifier } = event;
      (new AjaxRequest(TYPO3.settings.ajaxUrls.dashboard_widget_remove))
        .post({
          dashboard: this.currentDashboard.identifier,
          identifier,
        })
        .then(async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.status === 'ok') {
            // drop widget
            this.currentDashboard.widgets = this.currentDashboard.widgets.filter((widget) => {
              return widget.identifier !== identifier;
            });
            // drop widget position
            for (const [dashboardSize, dashboardSizeSet] of Object.entries(this.currentDashboard.widgetPositions)) {
              const dashboardSizeNumber = Number(dashboardSize);
              this.currentDashboard.widgetPositions[dashboardSizeNumber] = dashboardSizeSet.filter((widgetPosition) => widgetPosition.identifier !== identifier);
            }
            this.requestUpdate();
          } else {
            Notification.error('', data.message);
          }
        });
    });

    // Try to move widget
    this.addEventListener(DashboardWidgetMoveIntendEvent.eventName, (event): void => {
      event.preventDefault();
      const { intend, identifier } = event;
      const widgetPosition = this.widgetPositionByIdentifier(identifier);

      switch (intend) {
        case DashboardWidgetMoveIntend.up:
          widgetPosition.y = Math.max(0, widgetPosition.y - 1);
          break;
        case DashboardWidgetMoveIntend.down:
          widgetPosition.y++;
          break;
        case DashboardWidgetMoveIntend.left:
          widgetPosition.x = Math.max(0, widgetPosition.x - 1);
          break;
        case DashboardWidgetMoveIntend.right:
          widgetPosition.x = Math.min(this.columns - widgetPosition.width, widgetPosition.x + 1);
          break;
        case DashboardWidgetMoveIntend.end:
          // Rearranging DOM elements in `widgetPositionsSort` will cause a focus-state loss,
          // caused by `insertBefore` limitation, see
          // https://developer.chrome.com/blog/movebefore-api#losing_state_during_dom_mutations
          if (document.activeElement instanceof HTMLElement && document.activeElement.closest('typo3-dashboard') === this) {
            // Keep a reference to the focussed element and re-set in `this.updated()`
            this.activeElementRef = document.activeElement;
          }
          this.widgetPositionsSort(this.currentDashboard.widgetPositions[this.columns]);

          this.dispatchEvent(new DashboardUpdateEvent(
            this.currentDashboard.identifier,
            this.currentDashboard.widgets,
            this.currentDashboard.widgetPositions
          ));
          return;
        default:
          return;
      }

      this.widgetPositionChange(this.currentDashboard.widgetPositions[this.columns], widgetPosition);

      this.updateComplete.then(() => {
        const item = this.getGridItemByIdentifier(identifier);
        if (item) {
          const smooth = intend !== DashboardWidgetMoveIntend.up;
          DomHelper.scrollIntoViewIfNeeded(item, smooth);
        }
      });
    });

    // Add dashboard
    this.addEventListener(DashboardAddEvent.eventName, (event): void => {
      event.preventDefault();
      const { preset, title } = event;
      (new AjaxRequest(TYPO3.settings.ajaxUrls.dashboard_dashboard_add))
        .post({
          preset,
          title
        })
        .then(async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.status === 'ok') {
            const currentDashboard = data.dashboard;
            this.dashboards.push(currentDashboard);
            const selectedDashboard = this.getDashboardByIdentifier(currentDashboard.identifier) || this.getDashboardFirst();
            this.selectDashboard(selectedDashboard);
            this.requestUpdate();
          } else {
            Notification.error('', data.message);
          }
        });
    });

    // Edit dashboard
    this.addEventListener(DashboardEditEvent.eventName, (event): void => {
      event.preventDefault();
      const { identifier, title } = event;
      (new AjaxRequest(TYPO3.settings.ajaxUrls.dashboard_dashboard_edit))
        .post({
          identifier,
          title,
        })
        .then(async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.status === 'ok') {
            const oldDashboard: DashboardInterface = this.dashboards.filter((dashboard: DashboardInterface): boolean => {
              return dashboard.identifier === identifier;
            })[0];
            const index = this.dashboards.indexOf(oldDashboard);
            const updatedDashboard = data.dashboard;
            this.dashboards[index] = updatedDashboard;
            if (oldDashboard.identifier === updatedDashboard.identifier) {
              this.selectDashboard(updatedDashboard);
            }
            this.requestUpdate();
          } else {
            Notification.error('', data.message);
          }
        });
    });

    // Update dashboard
    this.addEventListener(DashboardUpdateEvent.eventName, (event): void => {
      event.preventDefault();

      const {
        identifier,
        widgets,
        widgetPositions,
      } = event;
      (new AjaxRequest(TYPO3.settings.ajaxUrls.dashboard_dashboard_update))
        .post({
          identifier,
          widgets,
          widgetPositions,
        })
        .then(async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.status === 'ok') {
            const oldDashboard: DashboardInterface = this.dashboards.filter((dashboard: DashboardInterface): boolean => {
              return dashboard.identifier === identifier;
            })[0];
            const index = this.dashboards.indexOf(oldDashboard);
            const updatedDashboard = data.dashboard;
            this.dashboards[index] = updatedDashboard;
            if (oldDashboard.identifier === updatedDashboard.identifier) {
              this.selectDashboard(updatedDashboard);
            }
            this.requestUpdate();
          } else {
            Notification.error('', data.message);
          }
        });
    });

    // Delete dashboard
    this.addEventListener(DashboardDeleteEvent.eventName, (event): void => {
      event.preventDefault();
      const { identifier } = event;
      (new AjaxRequest(TYPO3.settings.ajaxUrls.dashboard_dashboard_delete))
        .post({
          identifier
        })
        .then(async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.status === 'ok') {
            this.dashboards = this.dashboards.filter((dashboard: DashboardInterface): boolean => {
              return dashboard.identifier !== identifier;
            });
            const selectedDashboard = this.getDashboardFirst();
            this.selectDashboard(selectedDashboard);
            this.requestUpdate();
          } else {
            Notification.error('', data.message);
          }
        });
    });
  }

  public override connectedCallback() {
    super.connectedCallback();

    // Attach resize observer to set available columns
    this.resizeObserver = new ResizeObserver((entries) => {
      for (const entry of entries) {
        const { width } = entry.contentRect;
        if (width > 950) {
          this.columns = 4;
        } else if (width > 750) {
          this.columns = 2;
        } else {
          this.columns = 1;
        }
      }
    });
    this.resizeObserver.observe(this);

    this.mql = window.matchMedia('(prefers-reduced-motion: reduce)');
    this.mqListener(this.mql);
    this.mql.addEventListener('change', this.mqListener);
  }

  public override disconnectedCallback() {
    super.disconnectedCallback();

    this.resizeObserver?.disconnect();
    this.resizeObserver = null;

    this.mql?.removeEventListener('change', this.mqListener);
    this.mql = null;
  }

  protected readonly mqListener = (mql: MediaQueryList|MediaQueryListEvent): void => {
    this.prefersReducedMotion = mql.matches;
  }

  protected override firstUpdated(): void {
    this.load();
  }

  protected override updated(): void {
    if (this.activeElementRef) {
      this.activeElementRef.focus();
      this.activeElementRef = null;
    }
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    if (this.loading) {
      return this.renderLoader();
    }

    return html`
      ${this.renderHeader()}
      <div class="dashboard-container"
        @dragend=${this.handleDragEnd}
        @dragover=${this.handleDragOver}
        @dragstart=${this.handleDragStart}
        >
        ${this.renderContent()}
        <div class="dashboard-dragging-container"></div>
      </div>
      ${this.renderFooter()}
    `;
  }

  private async load(): Promise<void> {
    this.loading = true;
    this.dashboards = await this.fetchDashboards();
    const currentDashboardIdentifier = ClientStorage.get(this.clientStorageIdentifier);
    const selectedDashboard = this.getDashboardByIdentifier(currentDashboardIdentifier) || this.getDashboardFirst();
    this.selectDashboard(selectedDashboard);
    this.loading = false;
  }

  private async fetchData(url: string): Promise<unknown> {
    try {
      return (await new AjaxRequest(url).get({ cache: 'no-cache' })).resolve();
    } catch (error: unknown) {
      console.error(error);
      return [];
    }
  }

  private async fetchPresets(): Promise<DashboardPresetInterface[]> {
    const data = await this.fetchData(TYPO3.settings.ajaxUrls.dashboard_presets_get);
    return Object.values(data);
  }

  private async fetchCategories(): Promise<Categories> {
    const data = await this.fetchData(TYPO3.settings.ajaxUrls.dashboard_categories_get);
    return Categories.fromData(data as DataCategoriesInterface);
  }

  private async fetchDashboards(): Promise<DashboardInterface[]> {
    const data = await this.fetchData(TYPO3.settings.ajaxUrls.dashboard_dashboards_get);
    return data as DashboardInterface[];
  }

  private getDashboardByIdentifier(identifier: string): DashboardInterface | null {
    return this.dashboards.find(dashboard => dashboard.identifier === identifier) || null;
  }

  private getDashboardFirst(): DashboardInterface | null {
    return this.dashboards.length > 0 ? this.dashboards[0] : null;
  }

  private async createDashboard(): Promise<void> {
    const presets = await this.fetchPresets();
    const filteredPresets = presets.filter(preset => preset.showInWizard);

    const content = html`
      <form>
        <div class="form-group">
          <label class="form-label" for="dashboard-form-add-title">${lll('dashboard.title')}</label>
          <input class="form-control" id="dashboard-form-add-title" type="text" name="title" required="required">
        </div>
        <div class="dashboard-modal-items">
          ${repeat(filteredPresets, (preset: DashboardPresetInterface) => preset.identifier, (preset: DashboardPresetInterface, index: number) => html`
            <div class="dashboard-modal-item">
              <input
                type="radio"
                name="preset"
                value=${preset.identifier}
                class="dashboard-modal-item-checkbox"
                id="dashboard-form-add-preset-${preset.identifier}"
                ?checked=${index === 0}
              >
              <label for="dashboard-form-add-preset-${preset.identifier}" class="dashboard-modal-item-block">
                <span class="dashboard-modal-item-icon">
                  <typo3-backend-icon identifier=${preset.icon} size="medium"></typo3-backend-icon>
                </span>
                <span class="dashboard-modal-item-details">
                  <span class="dashboard-modal-item-title">${preset.title}</span>
                  <span class="dashboard-modal-item-description">${preset.description}</span>
                </span>
              </label>
            </div>
          `)}
        </div>
      </form>
    `;

    Modal.advanced({
      type: Modal.types.default,
      title: lll('dashboard.add'),
      size: Modal.sizes.medium,
      severity: SeverityEnum.notice,
      content,
      callback: (currentModal: ModalElement): void => {

        currentModal.addEventListener('typo3-modal-shown', (): void => {
          (currentModal.querySelector('#dashboard-form-add-title') as HTMLInputElement)?.focus();
        });

        currentModal.querySelector('form').addEventListener('submit', (e: Event): void => {
          e.preventDefault();
          const form = e.target as HTMLFormElement;
          const formData = new FormData(form);
          this.dispatchEvent(new DashboardAddEvent(
            formData.get('preset') as string,
            formData.get('title') as string,
          ));
          currentModal.hideModal();
        });

      },
      buttons: [
        {
          text: lll('dashboard.add.button.close'),
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (e, modal) => modal.hideModal(),
        },
        {
          text: lll('dashboard.add.button.ok'),
          btnClass: 'btn-primary',
          name: 'save',
          trigger: (e, modal) => modal.querySelector('form').requestSubmit(),
        },
      ]
    });
  }

  private editDashboard(dashboard: DashboardInterface): void {
    const content = html`
      <form>
        <div class="form-group">
          <label class="form-label" for="dashboard-form-edit-title">${lll('dashboard.title')}</label>
          <input class="form-control" id="dashboard-form-edit-title" type="text" name="title" value=${dashboard.title || ''} required="required">
        </div>
      </form>
    `;

    Modal.advanced({
      type: Modal.types.default,
      title: lll('dashboard.configure'),
      size: Modal.sizes.small,
      severity: SeverityEnum.notice,
      content,
      callback: (currentModal: ModalElement): void => {

        currentModal.addEventListener('typo3-modal-shown', (): void => {
          (currentModal.querySelector('#dashboard-form-edit-title') as HTMLInputElement)?.focus();
        });

        currentModal.querySelector('form').addEventListener('submit', (e: Event): void => {
          e.preventDefault();
          const form = e.target as HTMLFormElement;
          const formData = new FormData(form);
          this.dispatchEvent(new DashboardEditEvent(
            dashboard.identifier,
            formData.get('title') as string,
          ));
          currentModal.hideModal();
        });

      },
      buttons: [
        {
          text: lll('dashboard.configure.button.close'),
          btnClass: 'btn-default',
          name: 'cancel',
          trigger: (e, modal) => modal.hideModal(),
        },
        {
          text: lll('dashboard.configure.button.ok'),
          btnClass: 'btn-primary',
          name: 'save',
          trigger: (e, modal) => modal.querySelector('form').requestSubmit(),
        },
      ]
    });
  }

  private deleteDashboard(dashboard: DashboardInterface): void {
    const modal = Modal.confirm(
      lll('dashboard.delete'),
      lll('dashboard.delete.sure'),
      SeverityEnum.warning, [
        {
          text: lll('dashboard.delete.cancel'),
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
        },
        {
          text: lll('dashboard.delete.ok'),
          btnClass: 'btn-warning',
          name: 'delete',
        },
      ]
    );

    modal.addEventListener('button.clicked', (e: Event): void => {
      const target = e.target as HTMLButtonElement;
      if (target.getAttribute('name') === 'delete') {
        this.dispatchEvent(new DashboardDeleteEvent(dashboard.identifier));
      }
      modal.hideModal();
    });
  }

  private selectDashboard(dashboard: DashboardInterface | null): void {
    if (dashboard !== null) {
      ClientStorage.set(this.clientStorageIdentifier, dashboard.identifier);
    }
    this.currentDashboard = dashboard;
  }

  private async addWidget(): Promise<void> {
    topLevelModuleImport('@typo3/backend/new-record-wizard.js');

    const wizard = top.document.createElement('typo3-backend-new-record-wizard');
    wizard.searchPlaceholder = lll('widget.addToDashboard.searchLabel');
    wizard.searchNothingFoundLabel = lll('widget.addToDashboard.searchNotFound');
    wizard.categories = await this.fetchCategories();
    wizard.addEventListener(newRecordWizardEventName, async (event): Promise<void> => {
      const { identifier } = event.detail.item;
      const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.dashboard_widget_add)
        .post({
          dashboard: this.currentDashboard.identifier,
          type: identifier,
        });
      const data = await response.resolve();
      if (data.status === 'ok') {
        this.currentDashboard.widgets.push(data.widget);
        this.requestUpdate();
        await this.updateComplete;
        const item = this.getGridItemByIdentifier(data.widget.identifier);
        if (item) {
          DomHelper.scrollIntoViewIfNeeded(item, true);
          window.setTimeout(() => item.querySelector<HTMLButtonElement>('.widget-actions > button:first-child')?.focus({ preventScroll: true, focusVisible: false }), 50);
        }
      } else {
        Notification.error('', data.message);
      }
    });

    Modal.advanced({
      type: Modal.types.default,
      title: lll('widget.addToDashboard', this.currentDashboard.title),
      size: Modal.sizes.medium,
      severity: SeverityEnum.notice,
      content: wizard,
      callback: (currentModal: ModalElement): void => {
        currentModal.addEventListener('button.clicked', (): void => {
          currentModal.hideModal();
        });
      },
      buttons: [
        {
          text: lll('widget.add.button.close'),
          btnClass: 'btn-default',
          name: 'cancel',
        }
      ]
    });
  }

  private renderLoader(): TemplateResult {
    return html`
      <div class="dashboard-loader">
          <typo3-backend-spinner size="medium"></typo3-backend-spinner>
      </div>
    `;
  }

  private renderHeader(): TemplateResult {
    const createButton: TemplateResult = html`
      <button
        class="btn btn-primary btn-sm btn-dashboard-add-tab"
        title=${lll('dashboard.add')}
        @click=${() => { this.createDashboard() }}
      >
        <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
        <span class="visually-hidden">${lll('dashboard.add')}</span>
      </button>
    `;

    const editButton = this.currentDashboard !== null
      ? html`
        <button
          class="btn btn-default btn-sm"
          title=${lll('dashboard.configure')}
          @click=${() => { this.editDashboard(this.currentDashboard) }}
        >
          <typo3-backend-icon identifier="actions-cog" size="small"></typo3-backend-icon>
          <span class="visually-hidden">${lll('dashboard.configure')}</span>
        </button>
        `
      : nothing;

    const deleteButton = this.currentDashboard !== null
      ? html`
        <button
          class="btn btn-default btn-sm"
          title=${lll('dashboard.delete')}
          @click=${() => { this.deleteDashboard(this.currentDashboard) }}
        >
          <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
          <span class="visually-hidden">${lll('dashboard.delete')}</span>
        </button>
        `
      : nothing;

    return html`
      <div class="dashboard-header">
        <h1 class="visually-hidden">${this.currentDashboard?.title}</h1>
        <div class="dashboard-header-container">
          <div class="dashboard-tabs">
            ${repeat(this.dashboards, (dashboard: DashboardInterface) => dashboard.identifier, (dashboard: DashboardInterface) => html`
              <button
                @click=${() => { this.selectDashboard(dashboard); }}
                class="dashboard-tab${dashboard === this.currentDashboard ? ' dashboard-tab--active' : ''}"
              >
                ${dashboard.title}
              </button>
            `)}
            ${createButton}
          </div>
          ${editButton || deleteButton ? html`<div class="dashboard-configuration btn-toolbar" role="toolbar">${editButton}${deleteButton}</div>` : nothing}
        </div>
      </div>
    `;
  }

  private renderContent(): TemplateResult | typeof nothing {
    if (this.currentDashboard) {
      if (this.currentDashboard.widgets.length > 0) {
        this.initializeCurrentDashboard();

        const animation = {
          keyframeOptions: {
            duration: 250,
            fill: 'both' as FillMode,
          },
          in: fadeIn,
          out: fadeOut,
          skipInitial: true,
          disabled: this.prefersReducedMotion,
        };

        return html`
          <div
            class="dashboard-grid"
            style=${styleMap({ '--columns': this.columns })}
          >
            ${repeat(this.currentDashboard.widgetPositions[this.columns], (widget: DashboardWidgetPosition) => widget.identifier, (widget: DashboardWidgetPosition) => html`
              <div
                class="dashboard-item"
                style=${styleMap({ '--col-start': widget.x + 1, '--col-span': widget.width, '--row-start': widget.y + 1, '--row-span': widget.height })}
                data-widget-hash=${widget.identifier}
                data-widget-key=${this.widgetByIdentifier(widget.identifier)?.type}
                data-widget-identifier=${widget.identifier}
                draggable="true"
                @pointerenter=${(e: PointerEvent) => (e.target as HTMLElement).setAttribute('draggable', 'true')}
                @widgetRefresh="${() => this.handleLegacyWidgetRefreshEvent(widget)}"
                ${animate(animation)}
              >
                <typo3-dashboard-widget .identifier=${widget.identifier}></typo3-dashboard-widget>
              </div>
            `)}
          </div>
        `;
      }

      return html`
        <div class="dashboard-empty">
          <div class="dashboard-empty-content">
            <h3>${lll('dashboard.empty.content.title')}</h3>
            <p>${lll('dashboard.empty.content.description')}</p>
            <button
              title=${lll('widget.add')}
              class="btn btn-primary"
              @click=${() => { this.addWidget() }}
            >
              <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
              ${lll('dashboard.empty.content.button')}
            </button>
          </div>
        </div>
      `;
    }

    return nothing;
  }

  private renderFooter(): TemplateResult | typeof nothing{
    return this.currentDashboard === null ? nothing : html`
      <div class="dashboard-add-item">
        <button
          class="btn btn-primary btn-dashboard-add-widget"
          title=${lll('widget.addToDashboard', this.currentDashboard.title)}
          @click=${() => { this.addWidget() }}
        >
          <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
          <span class="visually-hidden">${lll('widget.addToDashboard', this.currentDashboard.title)}</span>
        </button>
      </div>
    `;
  }

  private getGridItemByIdentifier(identifier: string): HTMLElement | null {
    return this.querySelector(selector`.dashboard-item[data-widget-identifier="${identifier}"]`);
  }

  private handleDragStart(event: DragEvent): void {
    const target = event.target as HTMLElement;
    const itemElement = target.closest<HTMLElement>('.dashboard-item');
    if (itemElement === null) {
      event.preventDefault();
      return;
    }

    const widgetHeaderElement = document.elementFromPoint(event.clientX, event.clientY).closest('.widget-header');
    if (widgetHeaderElement === null) {
      event.preventDefault();
      return;
    }

    const identifier = itemElement.dataset.widgetIdentifier;
    const widgetPosition = this.widgetPositionByIdentifier(identifier);
    const rect = itemElement.getBoundingClientRect();
    const widgetElement = itemElement.querySelector('typo3-dashboard-widget');
    widgetElement.style.pointerEvents = 'none';

    this.dragInformation = {
      identifier,
      itemElement,
      widgetElement,
      height: widgetPosition.height,
      width: widgetPosition.width,
      offsetY: event.clientY - rect.top,
      offsetX: event.clientX - rect.left,
      currentY: widgetPosition.y,
      currentX: widgetPosition.x,
      initialPositions: this.currentDashboard.widgetPositions[this.columns].map(item => ({ ...item })),
    };

    const ghostImage = new Image();
    ghostImage.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
    event.dataTransfer.setDragImage(ghostImage, 0, 0);
    event.dataTransfer.setData('text/plain', '');
    event.dataTransfer.effectAllowed = 'move';

    itemElement.classList.add('dashboard-item-dragging');
    this.positionDraggingElement(event);
    this.draggingContainer.appendChild(widgetElement);
  }

  private positionDraggingElement(event: DragEvent) {
    const clamp = (value: number, min: number, max: number): number => Math.min(max, Math.max(min, value));
    const { itemElement, widgetElement } = this.dragInformation;
    const itemRect = itemElement.getBoundingClientRect();
    const containerRect = this.querySelector('.dashboard-container').getBoundingClientRect();
    const gap = 20;
    const x = clamp(
      event.clientX - this.dragInformation.offsetX,
      containerRect.left - gap,
      containerRect.left + containerRect.width - itemRect.width + gap
    );
    const y = Math.max(
      containerRect.top - gap,
      event.clientY - this.dragInformation.offsetY
    );
    widgetElement.style.left = `${x}px`;
    widgetElement.style.top = `${y}px`;
    widgetElement.style.width = `${itemRect.width}px`;
    widgetElement.style.height = `${itemRect.height}px`;
  }

  private handleDragEnd(): void {
    if (this.dragInformation) {
      const { itemElement, widgetElement } = this.dragInformation;
      itemElement.classList.remove('dashboard-item-dragging');
      itemElement.appendChild(widgetElement);
      widgetElement.removeAttribute('style');
      this.dragInformation = null;
      this.widgetPositionsSort(this.currentDashboard.widgetPositions[this.columns]);
      this.dispatchEvent(new DashboardUpdateEvent(
        this.currentDashboard.identifier,
        this.currentDashboard.widgets,
        this.currentDashboard.widgetPositions
      ));
    }
  }

  private handleDragOver(event: DragEvent): void {
    if (this.dragInformation) {
      event.preventDefault();
      event.dataTransfer.dropEffect = 'move';

      this.positionDraggingElement(event);

      const container = this.querySelector('.dashboard-grid');
      const rect = container.getBoundingClientRect();
      const gap = parseInt(getComputedStyle(container).gap, 10);
      const rowHeight = parseInt(getComputedStyle(container).gridAutoRows, 10) + gap;
      const colWidth = (rect.width + gap) / this.columns;
      const currentY = Math.max(0, event.clientY - rect.top - this.dragInformation.offsetY);
      const currentX = Math.max(0, event.clientX - rect.left - this.dragInformation.offsetX);
      const row = Math.max(0, Math.round(currentY / rowHeight));
      const col = Math.max(0, Math.min(Math.round(currentX / colWidth), this.columns - this.dragInformation.width))

      // Reduce dragover recalculations when nothing changed
      if (this.dragInformation.currentY !== row || this.dragInformation.currentX !== col) {
        this.dragInformation.currentY = row;
        this.dragInformation.currentX = col;
        if (this.dragOverTimeout) {
          clearTimeout(this.dragOverTimeout);
        }
        this.dragOverTimeout = window.setTimeout(() => {
          if (this.dragInformation) {
            const draggedWidgetPosition = this.widgetPositionByIdentifier(this.dragInformation.identifier);
            draggedWidgetPosition.y = this.dragInformation.currentY;
            draggedWidgetPosition.x = this.dragInformation.currentX;
            this.widgetPositionChange(this.currentDashboard.widgetPositions[this.columns], draggedWidgetPosition);
          }
        }, 100);
      }
    }
  }

  private handleLegacyWidgetRefreshEvent(widget: DashboardWidgetPosition): void {
    this.dispatchEvent(new DashboardWidgetRefreshEvent(widget.identifier));
  }

  private initializeCurrentDashboard(): void {
    this.currentDashboard.widgetPositions = this.currentDashboard.widgetPositions ?? {};
    let items = this.currentDashboard.widgetPositions?.[this.columns] ?? [];
    const widgetSizeWidth: Record<string, number> = { small: 1, medium: 2, large: 4 };
    const widgetSizeHeight: Record<string, number> = { small: 1, medium: 2, large: 3 };

    this.currentDashboard.widgets.forEach(widget => {
      if (items.find(widgetPosition => widgetPosition.identifier === widget.identifier) === undefined) {
        const height = widgetSizeHeight[widget.height] ?? 1;
        const width = widgetSizeWidth[widget.width] ?? 1;
        const widgetPosition: DashboardWidgetPosition = {
          identifier: widget.identifier,
          height: height,
          width: width < this.columns ? width : this.columns,
          y: 0,
          x: 0,
        };
        items.push(widgetPosition);
      }
    });

    items = this.widgetPositionsArrange(items);
    this.widgetPositionsCollapseRows(items);
    this.currentDashboard.widgetPositions[this.columns] = items;
  }

  private widgetByIdentifier(identifier: string): DashboardWidgetConfigurationInterface | null {
    return this.currentDashboard.widgets.find(item => item.identifier === identifier) ?? null;
  }

  private widgetPositionByIdentifier(identifier: string): DashboardWidgetPosition | null {
    return this.currentDashboard.widgetPositions[this.columns].find(item => item.identifier === identifier) ?? null;
  }

  private widgetPositionCanPlace(widget: DashboardWidgetPosition, col: number, row: number, occupiedCells: Set<string>): boolean {
    if (col < 0 || col > this.columns - widget.width || row < 0) {
      return false;
    }

    return occupiedCells.isDisjointFrom(createSet({ ...widget, x: col, y: row }));
  }

  private widgetPositionChange(items: DashboardWidgetPosition[], changedItem: DashboardWidgetPosition): void {
    // For the drag, we need to access the initial position of the widgets
    // and reevaluate them every time. If dragInformation is not set we
    // directly work on the dataset that is also used for later rendering.
    let initialPositions = structuredClone(this.dragInformation?.initialPositions ?? items);
    const index = initialPositions.findIndex((widget) => widget.identifier === changedItem.identifier);

    let origItem: DashboardWidgetPosition;
    if (index > -1) {
      const [item] = initialPositions.splice(index, 1);
      origItem = { ...item };
      item.y = changedItem.y;
      item.x = changedItem.x;
      initialPositions.unshift(item);
    }

    initialPositions = this.widgetPositionsArrange(initialPositions, this.dragInformation?.initialPositions ?? items, origItem);
    items.forEach(originalItem => {
      const updatedItem = initialPositions.find(copyItem => copyItem.identifier === originalItem.identifier);
      originalItem.y = updatedItem.y;
      originalItem.x = updatedItem.x;
    });
    this.widgetPositionsCollapseRows(items);
    this.requestUpdate();
  }

  private widgetTryPlacementInNeighbourCells(
    item: DashboardWidgetPosition,
    occupiedCells: Set<string>,
    allowedDistance?: { height: number, width: number }
  ): DashboardWidgetPosition | null {
    const maxCol = this.columns;
    // Try place left on the same row, moving max 1 position
    for (let newCol = item.x; newCol >= Math.max(0, item.x - item.width); newCol--) {
      if (this.widgetPositionCanPlace(item, newCol, item.y, occupiedCells)) {
        return {
          ...item,
          x: newCol
        }
      }
    }

    // Try place above, moving max 1 position
    for (let newRow = item.y; newRow >= 0; newRow--) {
      if (this.widgetPositionCanPlace(item, item.x, newRow, occupiedCells)) {
        return {
          ...item,
          y: newRow
        }
      }
    }

    // Try place right on the same row, moving max 1 position
    for (let newCol = item.x; newCol <= Math.min(maxCol, item.x + item.width); newCol++) {
      if (this.widgetPositionCanPlace(item, newCol, item.y, occupiedCells)) {
        return {
          ...item,
          x: newCol
        }
      }
    }

    // Try place below, moving max 1 position
    for (let newRow = item.y; newRow <= item.y + (allowedDistance?.height ?? 3); newRow++) {
      if (this.widgetPositionCanPlace(item, item.x, newRow, occupiedCells)) {
        return {
          ...item,
          y: newRow
        }
      }
    }

    return null;
  }

  private widgetPositionsArrange(
    items: DashboardWidgetPosition[],
    previousArrangement?: DashboardWidgetPosition[],
    movedItem?: DashboardWidgetPosition
  ): DashboardWidgetPosition[] {

    let occupiedCells = new Set<string>();

    const placeInCurrentPosition = (item: DashboardWidgetPosition) =>
      this.widgetPositionCanPlace(item, item.x, item.y, occupiedCells) ? { ...item } : null;

    const placeInNeighboursWithoutShiftingPreviousArrangements = (item: DashboardWidgetPosition) => previousArrangement === undefined ? null :
      this.widgetTryPlacementInNeighbourCells(
        item,
        // Find a "free" slot (without having to move existing widget) for this widget
        previousArrangement
          // Create an occupy map that contains all cells from the previous arrangement
          .reduce((set, item) => set.union(createSet(item)), new Set<string>())
          // …without the moved item (that place is considered free)
          .difference(createSet(movedItem))
          // …and the cells that have already been occupied in this run.
          .union(occupiedCells),
        // allow items to be moved to "free" places by the dimension of the moved item (allowing items to swap)
        movedItem
      );

    const placeInNeighbours = (item: DashboardWidgetPosition) =>
      this.widgetTryPlacementInNeighbourCells(item, occupiedCells);

    const placeSomewhere = (item: DashboardWidgetPosition) => {
      const row = Math.max(0, item.y);
      const col = Math.max(0, Math.min(this.columns - item.width, item.x));
      const minCol = Math.max(0, col);
      const maxCol = this.columns;
      for (let newRow = item.y; newRow < (row + 100); newRow++) {
        for (let newCol = minCol; newCol < maxCol; newCol++) {
          if (this.widgetPositionCanPlace(item, newCol, newRow, occupiedCells)) {
            return { ...item, x: newCol, y: newRow };
          }
        }
      }
      throw new Error('Logic error: could not occupy cells');
    };

    const occupy = (widgetPosition: DashboardWidgetPosition): DashboardWidgetPosition => {
      occupiedCells = occupiedCells.union(createSet(widgetPosition));
      return widgetPosition;
    };
    return items.map(item => occupy(
      placeInCurrentPosition(item) ??
      placeInNeighboursWithoutShiftingPreviousArrangements(item) ??
      placeInNeighbours(item) ??
      placeSomewhere(item)
    ));
  }

  private widgetPositionsCollapseRows(items: DashboardWidgetPosition[]): void {
    const rowsWithItems = new Set<number>();
    items.forEach(item => {
      for (let r = 0; r < item.height; r++) {
        rowsWithItems.add(item.y + r);
      }
    });
    const rowMapping: Record<number, number> = {};
    let newRowIndex = 0;
    for (let i = 0; i <= Math.max(...rowsWithItems); i++) {
      if (rowsWithItems.has(i)) {
        rowMapping[i] = newRowIndex++;
      }
    }

    items.forEach(item => {
      item.y = rowMapping[item.y];
    });
  }

  private widgetPositionsSort(items: DashboardWidgetPosition[]): void {
    items.sort((a, b) => {
      if (a.y !== b.y) {
        return a.y - b.y;
      }
      return a.x - b.x;
    });
  }
}

@customElement('typo3-dashboard-widget')
export class DashboardWidget extends LitElement {
  @property({ type: String, reflect: true }) public identifier: string;
  @state() moving: boolean = false;

  private triggerContentRenderedEvent: boolean = false;

  private readonly fetchTask = new Task(this, {
    args: () => [this.identifier] as const,
    task: async ([identifier], { signal }): Promise<DashboardWidgetInterface> => {
      const url = TYPO3.settings.ajaxUrls.dashboard_widget_get;
      const response = await new AjaxRequest(url)
        .withQueryArguments({ widget: identifier })
        .get({ signal });
      const data = await response.resolve();
      if (data.status !== 'ok') {
        throw new Error(data.message);
      }
      return data.widget;
    },
    onComplete: async () => {
      this.triggerContentRenderedEvent = true;
    },
    onError: (error: Error|AjaxResponse) => {
      console.error(`Error while retrieving widget [${this.identifier}]: ${
        error instanceof AjaxResponse ? `${error.response.status} ${error.response.statusText}` : error.message
      }`);
    },
  })

  private get widget(): DashboardWidgetInterface | null {
    return this.fetchTask.value ?? null;
  }

  public refresh(): void {
    this.handleRefresh();
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override updated(): void {
    if (this.triggerContentRenderedEvent) {
      this.triggerContentRenderedEvent = false;
      const { widget } = this;
      this.dispatchEvent(new DashboardWidgetContentRenderedEvent(widget));
      // Legacy event, kept for compatibility reasons
      this.dispatchEvent(new CustomEvent('widgetContentRendered', { bubbles: true, detail: this.widget.eventdata }));
    }
  }

  protected override render(): TemplateResult | symbol {
    const loader = html`
      <div class="widget-loader">
          <typo3-backend-spinner size="medium"></typo3-backend-spinner>
      </div>
    `;

    const widgetLabel = (widget: DashboardWidgetInterface | null) => widget?.label || 'ERROR';

    const widgetContent = (widget: DashboardWidgetInterface | null) => widget
      ? unsafeHTML(widget.content)
      : html`<div class="widget-content-main">${lll('widget.error')}</div>`;

    const refreshButton = (loading: boolean = false) => html`
      <button
        type="button"
        title=${lll('widget.refresh')}
        class="widget-action widget-action-refresh"
        @click=${this.handleRefresh}
      >
        ${loading ? html`<typo3-backend-spinner size="small"></typo3-backend-spinner>` : html`<typo3-backend-icon identifier="actions-refresh" size="small"></typo3-backend-icon>`}
        <span class="visually-hidden">${lll('widget.refresh')}</span>
      </button>
    `;

    const widgetRenderer = (widget: DashboardWidgetInterface | null, loading: boolean = false) => html`
      <div class="widget-header">
        <div class="widget-title">${widgetLabel(widget)}</div>
        <div class="widget-actions">
          ${widget?.options?.refreshAvailable ? refreshButton(loading) : nothing}
          <button
            type="button"
            title=${lll('widget.move')}
            class="widget-action widget-action-move"
            @click=${this.handleMoveClick}
            @focusout=${this.handleMoveFocusOut}
            @keydown=${this.handleMoveKeyDown}
          >
            <typo3-backend-icon identifier=${this.moving ? 'actions-thumbtack' : 'actions-move'} size="small"></typo3-backend-icon>
            <span class="visually-hidden">${lll('widget.move')}</span>
          </button>
          <button
            type="button"
            title=${lll('widget.remove')}
            class="widget-action widget-action-remove"
            @click=${this.handleRemove}
          >
            <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
            <span class="visually-hidden">${lll('widget.remove')}</span>
          </button>
        </div>
      </div>
      <div class="widget-content"
        @pointerenter=${(e: PointerEvent) => (e.target as HTMLElement).closest('.dashboard-item').removeAttribute('draggable')}
        @pointerleave=${(e: PointerEvent) => (e.target as HTMLElement).closest('.dashboard-item').setAttribute('draggable', 'true')}
        >${widgetContent(widget)}</div>
    `;

    const content = this.fetchTask.render({
      initial: () => nothing,
      error: () => widgetRenderer(null),
      pending: () => this.fetchTask.value ?
        // Preserve old content if refreshing, but show a spinning icon
        widgetRenderer(this.fetchTask.value, true) :
        // Delay the (initial) spinner by 80 ms to prevent flickering for fast connections.
        delay(80, () => loader),
      complete: (widget) => widgetRenderer(widget),
    });
    return html`<div class="widget ${this.moving ? ' widget-selected' : ''}">${content}</div>`;
  }

  private moveStart(): void {
    if (this.moving === false) {
      this.moving = true;
      this.dispatchEvent(new DashboardWidgetMoveIntendEvent(
        this.widget.identifier,
        DashboardWidgetMoveIntend.start,
      ));
    }
  }

  private moveEnd(): void {
    if (this.moving === true) {
      this.moving = false;
      this.dispatchEvent(new DashboardWidgetMoveIntendEvent(
        this.widget.identifier,
        DashboardWidgetMoveIntend.end,
      ));
    }
  }

  private handleMoveClick(): void
  {
    if (!this.moving) {
      this.moveStart();
    } else {
      this.moveEnd();
    }
  }

  private handleMoveFocusOut(): void {
    this.moveEnd();
  }

  private handleMoveKeyDown(event: KeyboardEvent): void {
    if (!this.moving) {
      return
    }

    const handledKeys = [
      'ArrowDown',
      'ArrowUp',
      'ArrowLeft',
      'ArrowRight',
      'Home',
      'End',
      'Enter',
      'Space',
      'Escape',
      'Tab',
    ];
    if (!handledKeys.includes(event.code) || event.altKey || event.ctrlKey) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    let intend: DashboardWidgetMoveIntend = DashboardWidgetMoveIntend.end;
    switch (event.code) {
      case 'Escape':
      case 'Enter':
      case 'Space':
        this.moveEnd();
        return;
      case 'ArrowUp':
        intend = DashboardWidgetMoveIntend.up;
        break;
      case 'ArrowDown':
        intend = DashboardWidgetMoveIntend.down;
        break;
      case 'ArrowLeft':
        intend = DashboardWidgetMoveIntend.left;
        break;
      case 'ArrowRight':
        intend = DashboardWidgetMoveIntend.right;
        break;
      default:
        return;
    }

    this.dispatchEvent(new DashboardWidgetMoveIntendEvent(
      this.widget.identifier,
      intend
    ));
  }

  private handleRefresh(): void {
    this.fetchTask.run()
  }

  private handleRemove(event: Event): void {
    const modal = Modal.confirm(
      lll('widget.remove.confirm.title'),
      lll('widget.remove.confirm.message'),
      SeverityEnum.warning, [
        {
          text: lll('widget.remove.button.close'),
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
        },
        {
          text: lll('widget.remove.button.ok'),
          btnClass: 'btn-warning',
          name: 'delete',
        },
      ]
    );

    modal.addEventListener('button.clicked', (e: Event): void => {
      const target = e.target as HTMLButtonElement;
      if (target.getAttribute('name') === 'delete') {
        this.dispatchEvent(new DashboardWidgetRemoveEvent(this.identifier));
      }
      modal.hideModal();
    });

    const trigger = event.currentTarget as HTMLButtonElement;
    modal.addEventListener('typo3-modal-hide', (): void => {
      trigger?.focus();
    });
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-dashboard': Dashboard;
    'typo3-dashboard-widget': DashboardWidget;
  }
  interface HTMLElementEventMap {
    [DashboardWidgetContentRenderedEvent.eventName]: DashboardWidgetContentRenderedEvent;
    [DashboardWidgetMoveIntendEvent.eventName]: DashboardWidgetMoveIntendEvent;
    [DashboardWidgetRemoveEvent.eventName]: DashboardWidgetRemoveEvent;
    [DashboardWidgetRefreshEvent.eventName]: DashboardWidgetRefreshEvent;
    [DashboardAddEvent.eventName]: DashboardAddEvent;
    [DashboardEditEvent.eventName]: DashboardEditEvent;
    [DashboardUpdateEvent.eventName]: DashboardUpdateEvent;
    [DashboardDeleteEvent.eventName]: DashboardDeleteEvent;

    [newRecordWizardEventName]: CustomEvent<NewRecordWizardItemSelectedEventInterface>;
  }
  interface FocusOptions {
    focusVisible: boolean;
  }
}
