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
define(["require","exports","TYPO3/CMS/Backend/GridEditor"],(function(t,i,r){"use strict";Object.defineProperty(i,"__esModule",{value:!0}),describe("TYPO3/CMS/Backend/GridEditorTest:",(function(){describe("tests for stripMarkup",(function(){it("works with string which contains html markup only",(function(){expect(r.GridEditor.stripMarkup("<b>foo</b>")).toBe("")})),it("works with string which contains html markup and normal text",(function(){expect(r.GridEditor.stripMarkup("<b>foo</b> bar")).toBe(" bar")}))}))}))}));