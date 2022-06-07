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
var __importDefault=this&&this.__importDefault||function(t){return t&&t.__esModule?t:{default:t}};define(["require","exports","jquery"],(function(t,e,r){"use strict";r=__importDefault(r);return new class{initialize(t){(0,r.default)(document).on("keyup",t,t=>{const e=(0,r.default)(t.currentTarget),o=e.val(),l=new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$","g"),a=new RegExp("^(?=.{8,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$","g"),n=new RegExp("(?=.{8,}).*","g");0===o.length?e.attr("style","background-color:#FBB19B; border:1px solid #DC4C42"):n.test(o)?l.test(o)?e.attr("style","background-color:#CDEACA; border:1px solid #58B548"):(a.test(o),e.attr("style","background-color:#FBFFB3; border:1px solid #C4B70D")):e.attr("style","background-color:#FBB19B; border:1px solid #DC4C42")})}}}));