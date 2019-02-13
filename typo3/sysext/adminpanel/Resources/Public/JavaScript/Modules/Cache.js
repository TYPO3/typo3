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
"use strict";var TYPO3;!function(t){var n=function(){this.buttons=Array.from(document.querySelectorAll('[data-typo3-role="clearCacheButton"]')),this.buttons.forEach(function(t){t.addEventListener("click",function(){var n=t.dataset.typo3AjaxUrl,e=new XMLHttpRequest;e.open("GET",n),e.send(),e.onload=function(){location.reload()}})})};(TYPO3||(TYPO3={})).Cache=n}(),window.addEventListener("load",function(){return new TYPO3.Cache},!1);