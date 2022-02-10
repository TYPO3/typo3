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
import{GridEditor}from"@typo3/backend/grid-editor.js";describe("TYPO3/CMS/Backend/GridEditorTest:",()=>{describe("tests for stripMarkup",()=>{it("works with string which contains html markup only",()=>{expect(GridEditor.stripMarkup("<b>'formula': \"x > y\"</b>")).toBe("'formula': \"x > y\"")}),it("works with string which contains html markup and normal text",()=>{expect(GridEditor.stripMarkup("<b>foo</b> bar")).toBe("foo bar")})})});