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
import{topDocument as a}from"@typo3/core/utility/top-window.js";class o{static isCopyModifierFromEvent(t){return t.dataTransfer.dropEffect==="copy"?!0:t.dataTransfer.dropEffect==="move"?!1:navigator.userAgent.includes("Mac")?t.dataTransfer.effectAllowed==="copy"||t.altKey:t.ctrlKey}static updateEventAndTooltipToReflectCopyMoveIntention(t){const e=o.isCopyModifierFromEvent(t);t.dataTransfer.dropEffect=e?"copy":"move",a.dispatchEvent(new CustomEvent("typo3:drag-tooltip:metadata-update",{detail:{statusIconIdentifier:e?"actions-duplicate":"actions-move"}}))}}export{o as default};
