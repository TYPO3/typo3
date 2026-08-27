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
 * Module: @typo3/backend/layout-module/column-restriction
 *
 * Client-side mirror of the server-side backend layout colPos restriction
 * (\TYPO3\CMS\Backend\View\BackendLayoutView::isCTypeAllowedInColPosByPage()).
 * Used by drag-drop and paste in the page layout module to avoid offering
 * actions that the DataHandler would reject anyway.
 *
 * @internal
 */

function toList(value: string | undefined): string[] {
  return (value ?? '')
    .split(',')
    .map((item: string): string => item.trim())
    .filter((item: string): boolean => item !== '');
}

/**
 * Evaluates whether the given CType is allowed in the column described by the
 * "data-allowed-content-types" / "data-disallowed-content-types" attributes.
 * Mirrors the server-side logic: disallowed wins, an empty allow list allows all.
 */
export function isContentTypeAllowedInColumn(cType: string, columnElement: HTMLElement | null): boolean {
  if (columnElement === null) {
    return true;
  }

  const disallowed = toList(columnElement.dataset.disallowedContentTypes);
  if (disallowed.includes(cType)) {
    return false;
  }

  const allowed = toList(columnElement.dataset.allowedContentTypes);
  if (allowed.length > 0 && !allowed.includes(cType)) {
    return false;
  }

  return true;
}

/**
 * Resolves the column of the given element (nearest ancestor carrying a colPos)
 * and evaluates whether the CType is allowed there.
 */
export function isContentTypeAllowedForTarget(cType: string, target: HTMLElement): boolean {
  return isContentTypeAllowedInColumn(cType, target.closest('[data-colpos]'));
}
