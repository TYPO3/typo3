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
define(["require","exports","../Enum/Viewport/ScaffoldIdentifier","nprogress"],(function(e,t,n,r){"use strict";return class{static start(){r.configure({parent:n.ScaffoldIdentifierEnum.contentModule,showSpinner:!1}),r.start()}static finish(){r.done()}}}));