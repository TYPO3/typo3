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
class o{static parents(e,t){const n=[];let l;for(;(l=e.parentElement.closest(t))!==null;)e=l,n.push(l);return n}static scrollableParent(e){let t=e.parentElement;for(;t;){const l=window.getComputedStyle(t).overflowY;if(l==="auto"||l==="scroll")return t;t=t.parentElement}return document.documentElement}static nextAll(e){const t=[];let n=e.nextElementSibling;for(;n!==null;)t.push(n),n=n.nextElementSibling;return t}static isRTL(){return window.getComputedStyle(document.documentElement).getPropertyValue("direction")==="rtl"}static scrollIntoViewIfNeeded(e,t=!1){if(!t&&"scrollIntoViewIfNeeded"in e&&typeof e.scrollIntoViewIfNeeded=="function")e.scrollIntoViewIfNeeded(!0);else{const n=e.getBoundingClientRect();n.top>=0&&n.left>=0&&n.bottom<=(window.innerHeight||document.documentElement.clientHeight)&&n.right<=(window.innerWidth||document.documentElement.clientWidth)||(t?e.scrollIntoView({behavior:"smooth",block:"center",inline:"center"}):e.scrollIntoView())}}}export{o as default};
