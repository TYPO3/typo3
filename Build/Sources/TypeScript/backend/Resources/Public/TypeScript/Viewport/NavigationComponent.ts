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
 * This interface defines the very minimum a navigation component needs to contain.
 *
 * It is mainly used in the NavigationContainer to load Components.
 */
export interface NavigationComponent {
  // A unique identifier, e.g. "PageTree"
  getName(): string;
  // Used if the component should refresh itself
  refresh?(): void;
  // Used to select an item inside the component
  select(item: any): void;
  // Apply any callback, and allow any item to be handed into the callback function
  apply(callback: Function): void;
}
