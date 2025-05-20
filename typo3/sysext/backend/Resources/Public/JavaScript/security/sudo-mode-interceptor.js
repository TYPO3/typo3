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
const s=async(o,a)=>{const n=o.clone(),t=await a(o);if(t.status===422){const{initiateSudoModeModal:e}=await import("@typo3/backend/security/element/sudo-mode.js"),i=await t.json();try{await e(i.sudoModeInitialization)}catch{return t}return a(n)}return t};export{s as sudoModeInterceptor};
