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
import"@typo3/backend/element/progress-bar-element.js";class t{constructor(e){this.element=document.createElement("typo3-backend-progress-bar"),this.cell=document.createElement("td"),this.cell.classList.add("col-progress"),this.cell.colSpan=e,this.cell.append(this.element)}update(e,l){e!==null&&(this.element.value=e),this.element.label=l}finalize(e,l){this.element.value=100,this.element.severity=e,this.element.label=l}remove(){this.cell.remove()}}export{t as UploadProgress};
