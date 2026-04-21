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

  // Horizontal scrolling may live on an inner container (e.g. the page module's
  // .t3-grid-container with overflow-x: auto) rather than the document, so target
  // the nearest horizontally scrollable ancestor of the drag source if any exists.
  const horizontalElement = findHorizontalScrollAncestor(event.target as Element | null) ?? scrollingElement;

  let scrollSpeedVertical = 0;
  let scrollSpeedHorizontal = 0;
  let running = true;
  let lastFrameTime = performance.now();

  // Disable smooth scrolling for the duration of the drag and restore on dragend
  const originalVerticalScrollBehavior = scrollingElement.style.scrollBehavior;
  const originalHorizontalScrollBehavior = horizontalElement.style.scrollBehavior;
  scrollingElement.style.scrollBehavior = 'auto';
  horizontalElement.style.scrollBehavior = 'auto';

  const tick = (now: number): void => {
    const dt = (now - lastFrameTime) / 1000;
    lastFrameTime = now;

    const maxVertical = scrollingElement.scrollHeight - scrollingElement.clientHeight;
    const maxHorizontal = horizontalElement.scrollWidth - horizontalElement.clientWidth;
    scrollingElement.scrollTop = Math.max(0, Math.min(scrollingElement.scrollTop + scrollSpeedVertical * dt, maxVertical));
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
    scrollingElement.style.scrollBehavior = originalVerticalScrollBehavior;
    horizontalElement.style.scrollBehavior = originalHorizontalScrollBehavior;
  }, { once: true });
}

function findHorizontalScrollAncestor(element: Element | null): HTMLElement | null {
  let current = element;
  while (current instanceof HTMLElement) {
    if (current.scrollWidth > current.clientWidth) {
      const overflowX = window.getComputedStyle(current).overflowX;
      if (overflowX === 'auto' || overflowX === 'scroll') {
        return current;
      }
    }
    current = current.parentElement;
  }
  return null;
}
