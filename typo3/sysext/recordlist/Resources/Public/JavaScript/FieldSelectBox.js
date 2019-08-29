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
define(["require","exports","jquery"],function(e,c,n){"use strict";return new(function(){return function(){n(function(){n(".fieldSelectBox .checkAll").change(function(e){var c=n(e.currentTarget).prop("checked");n(".fieldSelectBox tbody").find(":checkbox").each(function(e,r){n(r).prop("disabled")||n(r).prop("checked",c)})})})}}())});