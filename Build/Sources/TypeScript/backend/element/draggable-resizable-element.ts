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

import { html, LitElement, TemplateResult, PropertyValues } from 'lit';
import { customElement, property, state } from 'lit/decorators';

interface Position {
  x: number;
  y: number;
}

enum Action {
  move = 'move',
  resizeN = 'resizeN',
  resizeE = 'resizeE',
  resizeS = 'resizeS',
  resizeW = 'resizeW',
  resizeSE = 'resizeSE',
  resizeSW = 'resizeSW',
  resizeNE = 'resizeNE',
  resizeNW = 'resizeNW',
}

const resizeNorth = [Action.resizeNW, Action.resizeN, Action.resizeNE];
const resizeEast = [Action.resizeNE, Action.resizeE, Action.resizeSE];
const resizeSouth = [Action.resizeSE, Action.resizeS, Action.resizeSW];
const resizeWest = [Action.resizeSW, Action.resizeW, Action.resizeNW];

export interface DraggableResizableEventDetail {
  action?: Action;
  originOffset?: Offset;
}

export interface DraggableResizableEvent extends CustomEvent {
  readonly detail: DraggableResizableEventDetail;
}

export interface PointerEventNames {
  touchStart?: string[],
  touchMove?: string[],
  touchEnd?: string[],
  pointerDown: string[],
  pointerMove: string[],
  pointerUp: string[],
}

export class Offset {
  constructor(public left: number, public top: number, public width: number, public height: number) {}

  get right(): number {
    return this.left + this.width;
  }

  get bottom(): number {
    return this.top + this.height;
  }

  public clone(): Offset {
    return new Offset(this.left, this.top, this.width, this.height);
  }
}

/**
 * Module: @typo3/backend/element/typo3-backend-draggable-resizable
 *
 * Emitted events:
 * + @draggable-resizable-started with { action: Action, originOffset: Offset }
 * + @draggable-resizable-updated with { action: Action, originOffset: Offset }
 * + @draggable-resizable-finished with { action: Action, originOffset: Offset }
 *
 * @example
 * <typo3-backend-draggable-resizable offset="..." container="..."></typo3-backend-draggable-resizable>
 *
 * const element = document.create('typo3-backend-draggable-resizable offset');
 * element.offset = new Offset(0, 0, 1000, 500); // { left, top, width, height }
 * element.container = document.querySelector('#container');
 * document.appendChild(element);
 */
@customElement('typo3-backend-draggable-resizable')
export class DraggableResizableElement extends LitElement {
  @property({ type: Object, reflect: true }) offset: Offset = null;
  @property({ type: HTMLElement }) container: HTMLElement = null;
  @property({ type: Object }) pointerEventNames: PointerEventNames = {
    pointerDown: ['mousedown'],
    pointerMove: ['mousemove'],
    pointerUp: ['mouseup'],
  }
  @property({ type: Boolean, reflect: true }) private reverting = false;

  @state() private action: Action = null;
  @state() private windowRef: Window = window;
  @state() private documentRef: Document = document;

  private originOffset: Offset = null;
  private originPosition: Position = null;

  public get document(): Document {
    return this.documentRef;
  }

  public get window(): Window {
    return this.windowRef;
  }

  /**
   * In case this component is used in a modal, the actual DOM might be located in
   * `top.window.document`. This setter allows adjusting the DOM context, which is
   * also relevant for assigning proper CSP nonce values for different IFRAMEs.
   */
  public set window(windowRef: Window) {
    this.windowRef = windowRef;
    this.documentRef = windowRef.document;
  }

  /**
   * Reverts the position/dimension back to `offset`, having a transition effect enabled.
   */
  public revert(offset: Offset): void {
    this.reverting = true;
    this.offset = offset;
    // remove state after `transition-duration: 0.5s`
    setTimeout(() => this.reverting = false, 500);
  }

  public override connectedCallback(): void {
    super.connectedCallback();
    if (!(this.container instanceof HTMLElement)) {
      this.container = this.parentElement;
    }
    this.pointerEventNames.pointerDown.forEach((name: string): void =>
      this.documentRef.addEventListener(name, this.handleStart.bind(this), true));
    this.pointerEventNames.pointerMove.forEach((name: string): void =>
      this.documentRef.addEventListener(name, this.handleUpdate.bind(this), true));
    this.pointerEventNames.pointerUp.forEach((name: string): void =>
      this.documentRef.addEventListener(name, this.handleFinish.bind(this), true));
  }

  public override disconnectedCallback(): void {
    super.disconnectedCallback();
    this.pointerEventNames.pointerDown.forEach((name: string): void =>
      this.documentRef.removeEventListener(name, this.handleStart.bind(this), true));
    this.pointerEventNames.pointerMove.forEach((name: string): void =>
      this.documentRef.removeEventListener(name, this.handleUpdate.bind(this), true));
    this.pointerEventNames.pointerUp.forEach((name: string): void =>
      this.documentRef.removeEventListener(name, this.handleFinish.bind(this), true));
  }

  protected override render(): TemplateResult {
    return html`
      <div id="t3js-cropper-focus-area" class="cropper-focus-area ui-draggable ui-draggable-handle ui-resizable">
        <div class="ui-resizable-handle ui-resizable-n" data-resize="n"></div>
        <div class="ui-resizable-handle ui-resizable-e" data-resize="e"></div>
        <div class="ui-resizable-handle ui-resizable-s" data-resize="s"></div>
        <div class="ui-resizable-handle ui-resizable-w" data-resize="w"></div>
        <div class="ui-resizable-handle ui-resizable-se ui-icon ui-icon-gripsmall-diagonal-se" data-resize="se"></div>
        <div class="ui-resizable-handle ui-resizable-sw" data-resize="sw"></div>
        <div class="ui-resizable-handle ui-resizable-ne" data-resize="ne"></div>
        <div class="ui-resizable-handle ui-resizable-nw" data-resize="nw"></div>
      </div>
    `;
  }

  protected override update(changedProperties: PropertyValues): void {
    super.update(changedProperties);
    Object.assign(this.style, this.getOffsetStyles(this.offset));
  }

  protected override createRenderRoot(): HTMLElement | DocumentFragment {
    return this;
  }

  private handleStart(evt: MouseEvent): void {
    const target = evt.target as HTMLElement;
    if (evt.buttons !== 1 || !this.contains(target)) {
      return;
    }

    if (target.dataset.resize) {
      const actionName = 'resize' + target.dataset.resize.toUpperCase();
      this.action = Action[actionName as keyof typeof Action];
    } else {
      this.action = Action.move;
    }
    this.reverting = false;
    this.originOffset = this.offset.clone();
    this.originPosition = { x: evt.clientX, y: evt.clientY };

    this.dispatchEvent(this.createEvent(
      'draggable-resizable-started',
      { action: this.action, originOffset: this.originOffset }
    ));
  }

  private handleUpdate(evt: MouseEvent): void {
    if (!this.action) {
      return;
    }
    const deltaPos = {
      x: evt.clientX - this.originPosition.x,
      y: evt.clientY - this.originPosition.y,
    };
    // assign offset (and update styles implicitly)
    this.offset = this.adjustOffset(this.originOffset, deltaPos);
    this.dispatchEvent(this.createEvent(
      'draggable-resizable-updated',
      { action: this.action, originOffset: this.originOffset }
    ));
  }

  private handleFinish(): void {
    if (!this.action) {
      return;
    }
    this.dispatchEvent(this.createEvent(
      'draggable-resizable-finished',
      { action: this.action, originOffset: this.originOffset }
    ));
    this.action = null;
    this.originOffset = null;
    this.originPosition = null;
  }

  private adjustOffset(originOffset: Offset, delta: Position): Offset {
    // width & height cannot be lower
    const dimensionMin = 2;
    const containerBounds = this.container.getBoundingClientRect();
    const offset = originOffset.clone();

    if (this.action === Action.move) {
      offset.left = this.minMax(offset.left + delta.x, 0, containerBounds.width - offset.width);
      offset.top = this.minMax(offset.top + delta.y, 0, containerBounds.height - offset.height);
    }

    if (resizeNorth.includes(this.action)) {
      // hint: delta is negative here
      const deltaY = this.minMax(delta.y, -offset.top, offset.height - dimensionMin);
      offset.top += deltaY;
      offset.height -= deltaY;
    } else if (resizeSouth.includes(this.action)) {
      offset.height = this.minMax(offset.height + delta.y, dimensionMin, containerBounds.height - offset.top);
    }
    if (resizeWest.includes(this.action)) {
      // hint: delta is negative here
      const deltaX = this.minMax(delta.x, -offset.left, offset.width - dimensionMin);
      offset.left += deltaX;
      offset.width -= deltaX;
    } else if (resizeEast.includes(this.action)) {
      offset.width += delta.x;
    }

    return offset;
  }

  private minMax(value: number, min: number, max: number): number {
    if (value < min) {
      return min;
    }
    if (value > max) {
      return max;
    }
    return value;
  }

  private createEvent(type: string, detail?: DraggableResizableEventDetail): DraggableResizableEvent {
    return new CustomEvent(type, { detail, bubbles: true, composed: true });
  }

  private getOffsetStyles(offset: Offset): Record<string, string> {
    return {
      left: `${(offset.left)}px`,
      top: `${offset.top}px`,
      width: `${offset.width}px`,
      height: `${offset.height}px`,
    };
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-draggable-resizable': DraggableResizableElement;
  }
}
