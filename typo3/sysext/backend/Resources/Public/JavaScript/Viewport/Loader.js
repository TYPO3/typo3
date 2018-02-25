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
define(["require","exports","../Enum/Viewport/ScaffoldIdentifier"],function(n,t,e){"use strict";return function(){function t(){}return t.start=function(){n(["nprogress"],function(n){n.configure({parent:e.ScaffoldIdentifierEnum.contentModule,showSpinner:!1}),n.start()})},t.finish=function(){n(["nprogress"],function(n){n.done()})},t}()});