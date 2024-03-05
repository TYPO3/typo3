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

export enum DataTransferTypes {
  treenode = 'application/x-typo3-treenode',
  newTreenode = 'application/x-typo3-new-treenode+json',
  pages = 'application/x-typo3-record-pages+json',
  falResources = 'application/x-typo3-fal-resources+json',
  dragTooltip = 'application/x-typo3-drag-tooltip+json',
  content = 'application/x-typo3-content+json',
}
