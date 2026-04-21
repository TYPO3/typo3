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

/**
 * Module: @typo3/backend/layout-module/velocity-scroll
 *
 * Enables auto-scrolling while dragging near the viewport edge.
 *
 * Self-binds a delegated `dragstart` listener for all `[draggable="true"]`
 * elements on the page. May also be invoked directly:
 *
 * ```js
 *    // inside a dragstart event handler:
 *    initVelocityScroll(event);
 * ```
 */
import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';

const EDGE_RATIO = 0.2;
const MAX_STRENGTH_RATIO = 2.5;

DocumentService.ready().then((): void => {
  new RegularEvent('dragstart', (event: Event): void => {
    initVelocityScroll(event as DragEvent);
  }).delegateTo(document, '[draggable="true"]');
});

export function initVelocityScroll(event: DragEvent): void {
  const scrollingElement = document.scrollingElement;
  if (!(scrollingElement instanceof HTMLElement)) {
    console.warn('Scrolling element is not an HTMLElement. Velocity scroll will not work.');
    return;
  }

  // Scrolling may live on an inner container (e.g. the page module's
  // .module-body with overflow: auto) rather than the document, so target
  // the nearest scrollable ancestor of the drag source per axis if any exists.
  const target = event.target as Element | null;
  const verticalElement = findScrollAncestor(target, 'y') ?? scrollingElement;
  const horizontalElement = findScrollAncestor(target, 'x') ?? scrollingElement;

  let scrollSpeedVertical = 0;
  let scrollSpeedHorizontal = 0;
  let running = true;
  let lastFrameTime = performance.now();

  // Disable smooth scrolling for the duration of the drag and restore on dragend
  const originalVerticalScrollBehavior = verticalElement.style.scrollBehavior;
  const originalHorizontalScrollBehavior = horizontalElement.style.scrollBehavior;
  verticalElement.style.scrollBehavior = 'auto';
  horizontalElement.style.scrollBehavior = 'auto';

  const tick = (now: number): void => {
    const dt = (now - lastFrameTime) / 1000;
    lastFrameTime = now;

    const maxVertical = verticalElement.scrollHeight - verticalElement.clientHeight;
    const maxHorizontal = horizontalElement.scrollWidth - horizontalElement.clientWidth;
    verticalElement.scrollTop = Math.max(0, Math.min(verticalElement.scrollTop + scrollSpeedVertical * dt, maxVertical));
    horizontalElement.scrollLeft = Math.max(0, Math.min(horizontalElement.scrollLeft + scrollSpeedHorizontal * dt, maxHorizontal));

    if (running) {
      requestAnimationFrame(tick);
    }
  };
  requestAnimationFrame(tick);

  // Progress within the edge zone goes from 0 (just entered) to 1 (at the edge).
  // Squaring it makes scrolling accelerate noticeably near the edge.
  const axisStrength = (distance: number, edge: number, max: number): number =>
    distance < edge ? ((1 - distance / edge) ** 2) * max : 0;

  const autoScroll = (event: DragEvent): void => {
    const verticalEdge = window.innerHeight * EDGE_RATIO;
    const horizontalEdge = window.innerWidth * EDGE_RATIO;
    const maxVerticalStrength = window.innerHeight * MAX_STRENGTH_RATIO;
    const maxHorizontalStrength = window.innerWidth * MAX_STRENGTH_RATIO;

    scrollSpeedVertical =
      axisStrength(window.innerHeight - event.clientY, verticalEdge, maxVerticalStrength) -
      axisStrength(event.clientY, verticalEdge, maxVerticalStrength);
    scrollSpeedHorizontal =
      axisStrength(window.innerWidth - event.clientX, horizontalEdge, maxHorizontalStrength) -
      axisStrength(event.clientX, horizontalEdge, maxHorizontalStrength);
  };
  autoScroll(event);
  window.addEventListener('dragover', autoScroll);

  // Bind teardown on window: the source element may be detached or replaced
  // during the drop (drag-drop.ts re-renders content via AJAX), in which case
  // a `dragend` listener on the source element would never fire.
  window.addEventListener('dragend', (): void => {
    window.removeEventListener('dragover', autoScroll);
    scrollSpeedVertical = 0;
    scrollSpeedHorizontal = 0;
    running = false;
    verticalElement.style.scrollBehavior = originalVerticalScrollBehavior;
    horizontalElement.style.scrollBehavior = originalHorizontalScrollBehavior;
  }, { once: true });
}

function findScrollAncestor(element: Element | null, axis: 'x' | 'y'): HTMLElement | null {
  const sizeProp = axis === 'x' ? 'scrollWidth' : 'scrollHeight';
  const clientProp = axis === 'x' ? 'clientWidth' : 'clientHeight';
  const overflowProp = axis === 'x' ? 'overflowX' : 'overflowY';
  let current = element;
  while (current instanceof HTMLElement) {
    if (current[sizeProp] > current[clientProp]) {
      const overflow = window.getComputedStyle(current)[overflowProp];
      if (overflow === 'auto' || overflow === 'scroll') {
        return current;
      }
    }
    current = current.parentElement;
  }
  return null;
}
