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
define(["require","exports","jquery"],function(t,e,r){"use strict";return new(function(){function t(){}return t.prototype.initialize=function(t){r(document).on("keyup",t,function(t){var e=r(t.currentTarget),o=e.val(),n=new RegExp("^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$","g"),u=new RegExp("^(?=.{8,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$","g"),c=new RegExp("(?=.{8,}).*","g");0===o.length?e.attr("style","background-color:#FBB19B; border:1px solid #DC4C42"):c.test(o)?n.test(o)?e.attr("style","background-color:#CDEACA; border:1px solid #58B548"):(u.test(o),e.attr("style","background-color:#FBFFB3; border:1px solid #C4B70D")):e.attr("style","background-color:#FBB19B; border:1px solid #DC4C42")})},t}())});