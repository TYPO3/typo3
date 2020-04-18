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
define(["require","exports"],(function(e,t){"use strict";return new class{constructor(e=window,t=document){this.windowRef=e,this.documentRef=t}ready(){return new Promise((e,t)=>{if("complete"===this.documentRef.readyState)e(this.documentRef);else{const n=setTimeout(()=>{o(),t(this.documentRef)},3e4),o=()=>{this.windowRef.removeEventListener("load",i),this.documentRef.removeEventListener("DOMContentLoaded",i)},i=()=>{o(),clearTimeout(n),e(this.documentRef)};this.windowRef.addEventListener("load",i),this.documentRef.addEventListener("DOMContentLoaded",i)}})}}}));