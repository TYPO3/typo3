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
(function(){function c(t,i,n,o){return t=t+o,o>0&&t>n?t=i+(t-n-1):o<0&&t<i&&(t=n-(i-t-1)),String.fromCharCode(t)}function r(t,i){let n="";for(let o=0;o<t.length;o++){const e=t.charCodeAt(o);e>=43&&e<=58?n+=c(e,43,58,i):e>=64&&e<=90?n+=c(e,64,90,i):e>=97&&e<=122?n+=c(e,97,122,i):n+=t.charAt(o)}return n}function u(t,i,n){const o=window.open(t,i,n);return o&&o.focus(),o}function l(t,i,n){document.addEventListener(t,function(o){for(let e=o.target;e;e=e.parentNode!==document?e.parentNode:null)if("matches"in e){const a=e;a.matches(i)&&n(o,a)}})}l("click","a[data-mailto-token][data-mailto-vector]",function(t,i){t.preventDefault();const n=i.dataset,o=n.mailtoToken,e=parseInt(n.mailtoVector,10)*-1;document.location.href=r(o,e)}),l("click","a[data-window-url]",function(t,i){t.preventDefault();const n=i.dataset,o=n.windowUrl,e=n.windowTarget||null,a=n.windowFeatures||null;u(o,e,a)})})();
