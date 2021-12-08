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
class DocumentService{constructor(e=window,t=document){this.windowRef=e,this.documentRef=t}ready(){return new Promise((e,t)=>{if("complete"===this.documentRef.readyState)e(this.documentRef);else{const n=setTimeout(()=>{o(),t(this.documentRef)},3e4),o=()=>{this.windowRef.removeEventListener("load",d),this.documentRef.removeEventListener("DOMContentLoaded",d)},d=()=>{o(),clearTimeout(n),e(this.documentRef)};this.windowRef.addEventListener("load",d),this.documentRef.addEventListener("DOMContentLoaded",d)}})}}const documentService=new DocumentService;export default documentService;