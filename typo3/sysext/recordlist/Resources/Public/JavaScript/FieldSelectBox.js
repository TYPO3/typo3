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
define(["require","exports","jquery"],(function(e,c,r){"use strict";return new class{constructor(){r(()=>{r(".fieldSelectBox .checkAll").change(e=>{const c=r(e.currentTarget).prop("checked");r(".fieldSelectBox tbody").find(":checkbox").each((e,t)=>{r(t).prop("disabled")||r(t).prop("checked",c)})})})}}}));