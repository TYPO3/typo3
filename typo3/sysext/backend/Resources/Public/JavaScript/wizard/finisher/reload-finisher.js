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
import{html as o}from"lit";import{topWindow as e}from"@typo3/core/utility/top-window.js";class t{setConfig(n){this.config=n}async render(){return o`<typo3-backend-alert severity=0 heading=${this.config.labels.successTitle} message=${this.config.labels.successDescription} show-icon></typo3-backend-alert>`}async execute(){e!==window&&e.TYPO3?.Backend?.ContentContainer?e.TYPO3.Backend.ContentContainer.refresh():window.location.reload()}}export{t as default};
