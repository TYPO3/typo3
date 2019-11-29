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
define(["require","exports","TYPO3/CMS/Backend/BackendException"],(function(e,c,t){"use strict";Object.defineProperty(c,"__esModule",{value:!0}),describe("TYPO3/CMS/Backend/BackendException",()=>{it("sets exception message",()=>{const e=new t.BackendException("some message");expect(e.message).toBe("some message")}),it("sets exception code",()=>{const e=new t.BackendException("",12345);expect(e.code).toBe(12345)})})}));