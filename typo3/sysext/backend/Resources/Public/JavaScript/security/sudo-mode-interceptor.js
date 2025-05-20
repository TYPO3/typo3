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
export const sudoModeInterceptor=async(t,o)=>{const e=t.clone(),a=await o(t);if(422===a.status){const{initiateSudoModeModal:t}=await import("@typo3/backend/security/element/sudo-mode.js"),n=await a.json();try{await t(n.sudoModeInitialization)}catch{return a}return o(e)}return a};