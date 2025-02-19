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
import{Resizable as t}from"@typo3/backend/form-engine/element/modifier/resizable.js";import{Tabbable as l}from"@typo3/backend/form-engine/element/modifier/tabbable.js";import m from"@typo3/core/document-service.js";class n{constructor(e){this.element=null,m.ready().then(()=>{this.element=document.getElementById(e),t.enable(this.element),l.enable(this.element)})}}export{n as default};
