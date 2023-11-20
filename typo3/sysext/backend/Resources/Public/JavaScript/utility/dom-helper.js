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
export default class DomHelper{static parents(t,e){const l=[];let n;for(;null!==(n=t.parentElement.closest(e));)t=n,l.push(n);return l}static nextAll(t){const e=[];let l=null;for(;null!==(l=t.nextElementSibling);)e.push(l);return e}}