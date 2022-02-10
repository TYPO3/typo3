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
import{Resizable}from"@typo3/backend/form-engine/element/modifier/resizable.js";import{Tabbable}from"@typo3/backend/form-engine/element/modifier/tabbable.js";import DocumentService from"@typo3/core/document-service.js";class TextElement{constructor(e){this.element=null,DocumentService.ready().then(()=>{this.element=document.getElementById(e),Resizable.enable(this.element),Tabbable.enable(this.element)})}}export default TextElement;