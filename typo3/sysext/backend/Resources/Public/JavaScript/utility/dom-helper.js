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
export default class DomHelper{static parents(e,t){const n=[];let l;for(;null!==(l=e.parentElement.closest(t));)e=l,n.push(l);return n}static nextAll(e){const t=[];let n=null;for(;null!==(n=e.nextElementSibling);)t.push(n);return t}static scrollIntoViewIfNeeded(e){if("scrollIntoViewIfNeeded"in e&&"function"==typeof e.scrollIntoViewIfNeeded)e.scrollIntoViewIfNeeded(!0);else{const t=e.getBoundingClientRect();t.top>=0&&t.left>=0&&t.bottom<=(window.innerHeight||document.documentElement.clientHeight)&&t.right<=(window.innerWidth||document.documentElement.clientWidth)||e.scrollIntoView()}}}