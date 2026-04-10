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
 * A window carrying the backend's TYPO3 object.
 *
 * `Window.TYPO3` is declared partial, because any window may carry a half
 * built one. This type lets a caller read `TYPO3.settings` without narrowing
 * it again. It states which window holds the object, not which settings the
 * document that set it up has put into it.
 */
export type TopWindow = Window & { TYPO3: typeof TYPO3 };

/**
 * Walks a frame hierarchy outwards from `start` and returns the outermost
 * window carrying the TYPO3 configuration.
 */
function outermostTypo3Window(start: Window): TopWindow | null {
  let outermost: TopWindow | null = null;

  for (let candidate: Window = start; ; candidate = candidate.parent) {
    try {
      if (candidate.TYPO3) {
        outermost = candidate as TopWindow;
      }
    } catch {
      // A window of another origin ends the hierarchy this window belongs to.
      break;
    }

    // The outermost window is its own parent.
    if (candidate === candidate.parent) {
      break;
    }
  }

  return outermost;
}

/**
 * The window carrying the TYPO3 configuration.
 *
 * `top` is not the answer. An application embedding the backend sits at the
 * top of the frame hierarchy and carries no TYPO3 object, and the backend
 * below it nests frames of its own. The walk therefore goes outwards and keeps
 * the outermost window that carries TYPO3.
 *
 * The clickjacking protection header keeps other origins off that path, but
 * it is configurable and may be lifted to embed the backend elsewhere. The
 * browser refuses to read `TYPO3` across origins, so the walk ends at the
 * first window it is not allowed to read.
 *
 * The answer holds for the lifetime of this document, so it is resolved once.
 * A window above this one keeps its document as long as this one exists, and
 * received its TYPO3 object from an inline script before any frame below it
 * loaded. Whether this window carries TYPO3 itself does not change the answer.
 *
 * @internal
 */
export const topWindow: TopWindow = outermostTypo3Window(window) ?? window as TopWindow;

/**
 * The document that carries the backend's global events and the elements
 * outliving a single module.
 *
 * @internal
 */
export const topDocument: Document = topWindow.document;

/**
 * Returns the window carrying the TYPO3 configuration in the hierarchy that
 * opened this one.
 *
 * A popup is its own `top`, so a backend window opened from another one reads
 * its settings from the opener. The walk runs over that hierarchy too, because
 * an application embedding the backend surrounds the opener the same way.
 *
 * The clickjacking protection header governs which origins may embed the
 * backend, and says nothing about which may open it. The opener can therefore
 * come from any site, which the walk reports as an absent opener.
 *
 * Unlike the frames above this window, the opener may navigate or close while
 * this window lives, so the answer is resolved on every call.
 *
 * @internal
 */
export function openerTopWindow(): TopWindow | null {
  return opener ? outermostTypo3Window(opener) : null;
}
