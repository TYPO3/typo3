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
import{html as n}from"lit";class o{setConfig(e){this.config=e}async render(){return n`<typo3-backend-alert severity=0 heading=${this.config.labels.successTitle} message=${this.config.labels.successDescription} show-icon></typo3-backend-alert>`}async execute(){const e=this.config.data.url;if(!e){console.warn("Redirect finisher called without URL");return}window.opener&&!window.opener.closed&&window.self===window.top?(window.opener.location.href=e,window.close()):window.top&&window.top.TYPO3?.Backend?.ContentContainer?window.top.TYPO3.Backend.ContentContainer.setUrl(e):window.location.href=e}}export{o as default};
