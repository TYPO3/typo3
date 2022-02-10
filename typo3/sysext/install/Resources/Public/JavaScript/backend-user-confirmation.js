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
import DocumentService from"@typo3/core/document-service.js";class BackendUserConfirmation{constructor(){DocumentService.ready().then(()=>this.addFocusToFormInput())}addFocusToFormInput(){const o=document.getElementById("confirmationPassword");null!==o&&o.focus()}}export default new BackendUserConfirmation;