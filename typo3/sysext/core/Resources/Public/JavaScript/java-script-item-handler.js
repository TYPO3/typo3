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
if(document.currentScript){const n=document.currentScript.textContent.replace(/^\s*\/\*\s*|\s*\*\/\s*/g,""),r=JSON.parse(n),o=import("@typo3/core/java-script-item-processor.js").then(({JavaScriptItemProcessor:e})=>new e().processItems(r)),t=Symbol.for("@typo3/core/java-script-item-processor/pending-items"),s=window;s[t]??=new Set,s[t].add(new Promise(e=>{o.finally(e)}))}
