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
define(["require","exports","TYPO3/CMS/Backend/BackendException"],function(a,b,c){"use strict";Object.defineProperty(b,"__esModule",{value:!0}),describe("TYPO3/CMS/Backend/BackendException",function(){it("sets exception message",function(){var a=new c.BackendException("some message");expect(a.message).toBe("some message")}),it("sets exception code",function(){var a=new c.BackendException("",12345);expect(a.code).toBe(12345)})})});