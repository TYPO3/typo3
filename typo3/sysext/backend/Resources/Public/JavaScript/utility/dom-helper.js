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
class o{static parents(t,e){const n=[];let l;for(;(l=t.parentElement.closest(e))!==null;)t=l,n.push(l);return n}static scrollableParent(t){let e=t.parentElement;for(;e;){const l=window.getComputedStyle(e).overflowY;if(l==="auto"||l==="scroll")return e;e=e.parentElement}return document.documentElement}static scrollEventTarget(t){const e=this.scrollableParent(t);return e===document.documentElement?document:e}static nextAll(t){const e=[];let n=t.nextElementSibling;for(;n!==null;)e.push(n),n=n.nextElementSibling;return e}static scrollIntoViewIfNeeded(t,e=!1){if(!e&&"scrollIntoViewIfNeeded"in t&&typeof t.scrollIntoViewIfNeeded=="function")t.scrollIntoViewIfNeeded(!0);else{const n=t.getBoundingClientRect();n.top>=0&&n.left>=0&&n.bottom<=(window.innerHeight||document.documentElement.clientHeight)&&n.right<=(window.innerWidth||document.documentElement.clientWidth)||(e?t.scrollIntoView({behavior:"smooth",block:"center",inline:"center"}):t.scrollIntoView())}}}export{o as default};
