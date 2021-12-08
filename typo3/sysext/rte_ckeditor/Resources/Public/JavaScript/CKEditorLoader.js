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
let ckeditorPromise=null;function loadScript(e){return new Promise((o,r)=>{const t=document.createElement("script");t.async=!0,t.onerror=r,t.onload=e=>o(e),t.src=e,document.head.appendChild(t)})}export function loadCKEditor(){if(null===ckeditorPromise){const e=import.meta.url.replace(/\/[^\/]+\.js/,"/Contrib/ckeditor.js");ckeditorPromise=loadScript(e).then(()=>window.CKEDITOR)}return ckeditorPromise}