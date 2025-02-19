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
class i{static parents(n,t){const e=[];let l;for(;(l=n.parentElement.closest(t))!==null;)n=l,e.push(l);return e}static nextAll(n){const t=[];let e=n.nextElementSibling;for(;e!==null;)t.push(e),e=e.nextElementSibling;return t}static scrollIntoViewIfNeeded(n,t=!1){if(!t&&"scrollIntoViewIfNeeded"in n&&typeof n.scrollIntoViewIfNeeded=="function")n.scrollIntoViewIfNeeded(!0);else{const e=n.getBoundingClientRect();e.top>=0&&e.left>=0&&e.bottom<=(window.innerHeight||document.documentElement.clientHeight)&&e.right<=(window.innerWidth||document.documentElement.clientWidth)||(t?n.scrollIntoView({behavior:"smooth",block:"center",inline:"center"}):n.scrollIntoView())}}}export{i as default};
