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
export function getRecordFromName(n){const t=document.getElementById(n);return t?{name:n,component:t.dataset.component,navigationComponentId:t.dataset.navigationcomponentid,link:t.getAttribute("href")}:{name:n,component:"",navigationComponentId:"",link:""}}