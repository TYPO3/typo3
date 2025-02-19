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
import{MultiRecordSelectionSelectors as c}from"@typo3/backend/multi-record-selection.js";class n{static getEntityIdentifiers(t){const i=[];return t.checkboxes.forEach(o=>{const e=o.closest(c.elementSelector);e!==null&&e.dataset[t.configuration.idField]&&i.push(e.dataset[t.configuration.idField])}),i}}export{n as MultiRecordSelectionAction};
