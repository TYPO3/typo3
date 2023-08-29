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
"use strict";!function(){function t(t,n,e,o){return t+=o,o>0&&t>e?t=n+(t-e-1):o<0&&t<n&&(t=e-(n-t-1)),String.fromCharCode(t)}function n(t,n,e){document.addEventListener(t,(function(t){for(let o=t.target;o;o=o.parentNode!==document?o.parentNode:null)if("matches"in o){const a=o;a.matches(n)&&e(t,a)}}))}n("click","a[data-mailto-token][data-mailto-vector]",(function(n,e){n.preventDefault();const o=e.dataset,a=o.mailtoToken,c=-1*parseInt(o.mailtoVector,10);document.location.href=function(n,e){let o="";for(let a=0;a<n.length;a++){const c=n.charCodeAt(a);o+=c>=43&&c<=58?t(c,43,58,e):c>=64&&c<=90?t(c,64,90,e):c>=97&&c<=122?t(c,97,122,e):n.charAt(a)}return o}(a,c)})),n("click","a[data-window-url]",(function(t,n){t.preventDefault();const e=n.dataset;!function(t,n,e){const o=window.open(t,n,e);o&&o.focus()}(e.windowUrl,e.windowTarget||null,e.windowFeatures||null)}))}();