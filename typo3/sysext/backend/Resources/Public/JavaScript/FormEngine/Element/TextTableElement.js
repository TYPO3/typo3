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
import{Resizable}from"TYPO3/CMS/Backend/FormEngine/Element/Modifier/Resizable.js";import{Tabbable}from"TYPO3/CMS/Backend/FormEngine/Element/Modifier/Tabbable.js";import DocumentService from"TYPO3/CMS/Core/DocumentService.js";class TextTableElement{constructor(e){this.element=null,DocumentService.ready().then(()=>{this.element=document.getElementById(e),Resizable.enable(this.element),Tabbable.enable(this.element)})}}export default TextTableElement;