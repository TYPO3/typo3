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

import { html, LitElement, nothing, TemplateResult } from 'lit';
import { customElement, property, state } from 'lit/decorators';
import { BroadcastMessage } from '@typo3/backend/broadcast-message';
import BroadcastService from '@typo3/backend/broadcast-service';
import { DataTransferTypes } from '@typo3/backend/enum/data-transfer-types';
import type { PropertyValues, ReactiveElement } from '@lit/reactive-element';
import type { WritablePart } from '@typo3/core/utility/types';

/**
 * Contains basic types for allowing dragging + dropping in trees
 */

export interface DragDropThumbnail {
  src: string,
  width: number,
  height: number,
}

export interface DragTooltipMetadata {
  statusIconIdentifier?: string,
  tooltipIconIdentifier?: string,
  tooltipLabel?: string,
  tooltipDescription?: string,
  thumbnails?: DragDropThumbnail[],
}

@customElement('typo3-backend-drag-tooltip')
export class DragToolTip extends LitElement implements DragTooltipMetadata {
  /** Dragging is active (in this or another tab) */
  @property({ type: Boolean, reflect: true }) active: boolean = false;
  @property({ type: String, reflect: true }) statusIconIdentifier: string|null = 'apps-pagetree-drag-move-into';
  @property({ type: String }) tooltipIconIdentifier: string|null = null;
  @property({ type: String }) tooltipLabel: string;
  @property({ type: String }) tooltipDescription: string;
  @property({ type: Array }) thumbnails: DragDropThumbnail[] = [];
  /** This particular drag tooltip instance is active (drag item is being dragged over the tab of this instance) */
  @state() visible: boolean = false;
  @state() posX: number = 0;
  @state() posY: number = 0;
  @state() dragAllowed: boolean = false;

  protected skipNextUpdateBroadcast: boolean = false;
  protected eventAbortController: AbortController = null;

  protected ghostImage: HTMLImageElement;

  constructor() {
    super();
    // This creates a ghost image we are using as drag preview.
    //
    // We are building a custom preview, so this is a transparent image
    // to prevent the default behaviour of the browser to show a snapshot
    // of the dragged element.
    //
    // This only accepts drag images that are preloaded.
    // So we are creating this image early in the process.
    this.ghostImage = new Image();
    this.ghostImage.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=';
  }

  public override connectedCallback(): void {
    super.connectedCallback();
    const capture = true, passive = true;
    // own drags (including frames)
    // external drags
    window.addEventListener('dragover', this.updatePositionFromDragEvent, { capture, passive });
    // state of drags
    window.addEventListener('dragover', this.trackDragOverAllowed, { passive });
    // finish of own drags
    window.addEventListener('dragend', this.trackDragEnd, { capture, passive });
    window.addEventListener('dragstart', this.trackDragStart, { passive });

    document.addEventListener('typo3:drag-tooltip:visible', this.onBroadcastVisible);
    document.addEventListener('typo3:drag-tooltip:changedProperties', this.onBroadcastChangedProperties);
    document.addEventListener('typo3:drag-tooltip:metadata-update', this.onMetadataUpdate);
    document.addEventListener('typo3-iframe-loaded', this.onIframeLoaded);

    this.eventAbortController?.abort();
    this.eventAbortController = new AbortController();
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    const capture = true;
    //window.removeEventListener('drag', this.updatePositionFromDragEvent, { capture: true });
    window.removeEventListener('dragover', this.updatePositionFromDragEvent, { capture });
    window.removeEventListener('dragover', this.trackDragOverAllowed);
    window.removeEventListener('dragend', this.trackDragEnd, { capture });
    window.removeEventListener('dragstart', this.trackDragStart);

    document.removeEventListener('typo3:drag-tooltip:visible', this.onBroadcastVisible);
    document.removeEventListener('typo3:drag-tooltip:changedProperties', this.onBroadcastChangedProperties);
    document.removeEventListener('typo3:drag-tooltip:metadata-update', this.onMetadataUpdate);
    document.removeEventListener('typo3-iframe-loaded', this.onIframeLoaded);
    this.eventAbortController?.abort();
    this.eventAbortController = null;
  }

  public updatePositionFromDragEvent = (event: DragEvent): void => {
    this.visible = !(event.clientX === 0 && event.clientY === 0);
    const offset = this.calculateIframeOffset(event.view, window);
    this.posX = event.clientX + offset.x;
    this.posY = event.clientY + offset.y;
    if (this.visible) {
      this.broadcast('visible');
    }
  }

  public trackDragOverAllowed = (event: DragEvent): void => {
    this.dragAllowed = event.defaultPrevented;
  }

  protected reset() {
    this.active = true;
    this.visible = true;
    this.statusIconIdentifier = 'apps-pagetree-drag-move-into';
    this.tooltipIconIdentifier = '';
    this.tooltipLabel = '';
    this.tooltipDescription = '';
    this.thumbnails = [];
    this.posX = 0;
    this.posY = 0;
    this.dragAllowed = false;
  }

  protected override updated(changedProperties: PropertyValues<this>): void {
    if (this.skipNextUpdateBroadcast) {
      this.skipNextUpdateBroadcast = false;
      return;
    }

    const propertyNames = [...changedProperties.keys()].filter(
      (propName: keyof this) => (this.constructor as typeof ReactiveElement).elementProperties.get(propName).attribute !== false
    );

    if (propertyNames.length === 0) {
      return;
    }

    const newProperties = propertyNames.map((propName: keyof this) => [ propName, this[propName] ]);
    this.broadcast('changedProperties', Object.fromEntries(newProperties))
  }

  protected broadcast(eventName: string, payload?: unknown) {
    BroadcastService.post(new BroadcastMessage('drag-tooltip', eventName, payload || {}));
  }

  protected calculateIframeOffset(contentWindow: Window, currentWindow: Window): { x: number, y: number } {
    let x = 0, y = 0;

    if (contentWindow === currentWindow) {
      return { x, y }
    }

    const parentOffset = this.calculateIframeOffset(contentWindow.parent, currentWindow)
    x += parentOffset.x;
    y += parentOffset.y;

    const iframe = contentWindow.frameElement;
    if (iframe) {
      const rect = iframe.getBoundingClientRect();
      x += rect.x
      y += rect.y
    }

    return { x, y }
  }

  protected trackDragEnd = (): void => {
    this.active = false;
  }

  protected trackDragStart = (event: DragEvent): void => {
    if (event.defaultPrevented) {
      return;
    }

    if (event.dataTransfer.types.includes(DataTransferTypes.dragTooltip)) {
      event.dataTransfer.setDragImage(this.ghostImage, 0, 0);

      const metadata: DragTooltipMetadata = JSON.parse(event.dataTransfer.getData(DataTransferTypes.dragTooltip));
      this.reset();
      Object.assign(this, metadata);
      this.broadcast('visible');
    }
  }

  protected onMetadataUpdate = (event: CustomEvent<DragTooltipMetadata>): void => {
    const metadata = event.detail;
    Object.assign(this, metadata);
  }

  protected onBroadcastVisible = () => {
    // Another tab is dragging, hide our instance
    this.visible = false;
  }

  protected onBroadcastChangedProperties = (event: CustomEvent<{ payload: Partial<WritablePart<DragToolTip>> }>) => {
    const newProperties = event.detail.payload;
    Object.keys(newProperties).forEach((key: keyof WritablePart<DragToolTip>) => {
      (this[key] as unknown) = newProperties[key];
    });
    this.skipNextUpdateBroadcast = true;
  }

  protected onIframeLoaded = (event: Event) => {
    let win: Window;
    try {
      win = (event.target as HTMLElement).querySelector('iframe')?.contentWindow;
    } catch {
      return;
    }

    if (win) {
      this.eventAbortController?.abort();
      this.eventAbortController = new AbortController();
      const { signal } = this.eventAbortController;
      const capture = true, passive = true;
      win.addEventListener('dragover', this.updatePositionFromDragEvent, { capture, passive, signal });
      win.addEventListener('dragover', this.trackDragOverAllowed, { passive, signal });
      win.addEventListener('dragend', this.trackDragEnd, { capture, passive, signal });
      win.addEventListener('dragstart', this.trackDragStart, { passive, signal });
    }
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): typeof nothing | TemplateResult {
    if (!this.active || !this.visible) {
      return nothing;
    }

    if (this.posX === 0 && this.posY === 0) {
      return nothing;
    }

    return html`
      <div class="dragging-tooltip" style="top: ${this.posY + 18}px; left: ${this.posX + 18}px;">
        <div class="dragging-tooltip-control">
          <typo3-backend-icon identifier="${this.dragAllowed ? (this.statusIconIdentifier ?? 'actions-question') : 'actions-ban'}" size="small">
          </typo3-backend-icon>
        </div>
        <div class="dragging-tooltip-content">
          <div class="dragging-tooltip-content-icon">
            <typo3-backend-icon identifier="${this.tooltipIconIdentifier}" size="small"></typo3-backend-icon>
          </div>
          <div class="dragging-tooltip-content-label">
            ${this.tooltipLabel !== '' ? html`<div class="dragging-tooltip-content-name">${this.tooltipLabel}</div>` : nothing}
            ${this.tooltipDescription !== '' ? html`<div class="dragging-tooltip-content-description">${this.tooltipDescription}</div>` : nothing}
          </div>
          ${this.thumbnails.length === 0 ? nothing : html`
            <div class="dragging-tooltip-thumbnails">
              ${this.thumbnails.slice(0, 3).map(image => html`
                <img src="${image.src}" width="${image.width}" height="${image.height}">
              `)}
            </div>
          `}
        </div>
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-drag-tooltip': DragToolTip;
  }
}
