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
(function(){document.addEventListener("click",t=>{const n=t.target;if(!n.classList.contains("tx-indexedsearch-page-selector"))return;t.preventDefault();const e=n.dataset;document.getElementById(e.prefix+"_pointer").value=e.pointer,document.getElementById(e.prefix+"_freeIndexUid").value=e.freeIndexUid,document.forms.namedItem(e.prefix).submit()})})();
