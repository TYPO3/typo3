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
import{AbstractAction}from"@typo3/backend/action-button/abstract-action.js";import Icons from"@typo3/backend/icons.js";class DeferredAction extends AbstractAction{async execute(t){return Icons.getIcon("spinner-circle-light",Icons.sizes.small).then(e=>{t.innerHTML=e}),await this.executeCallback()}async executeCallback(){return await this.callback()}}export default DeferredAction;