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
"use strict";document.addEventListener("click",(e=>{const t=e.target;if(!t.classList.contains("tx-indexedsearch-page-selector"))return;e.preventDefault();const n=t.dataset;document.getElementById(n.prefix+"_pointer").value=n.pointer,document.getElementById(n.prefix+"_freeIndexUid").value=n.freeIndexUid,document.forms.namedItem(n.prefix).submit()}));