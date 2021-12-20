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
define(["require","exports","TYPO3/CMS/Backend/Icons"],(function(e,t,s){"use strict";Object.defineProperty(t,"__esModule",{value:!0}),describe("TYPO3/CMS/Backend/IconsTest:",()=>{describe("tests for Icons object",()=>{it("has all sizes",()=>{expect(s.sizes.small).toBe("small"),expect(s.sizes.default).toBe("default"),expect(s.sizes.large).toBe("large"),expect(s.sizes.overlay).toBe("overlay")}),it("has all states",()=>{expect(s.states.default).toBe("default"),expect(s.states.disabled).toBe("disabled")}),it("has all markupIdentifiers",()=>{expect(s.markupIdentifiers.default).toBe("default"),expect(s.markupIdentifiers.inline).toBe("inline")})}),describe("tests for Icons::getIcon",()=>{beforeEach(()=>{spyOn(s,"getIcon"),s.getIcon("test",s.sizes.small,null,s.states.default,s.markupIdentifiers.default)}),it("tracks that the spy was called",()=>{expect(s.getIcon).toHaveBeenCalled()}),it("tracks all the arguments of its calls",()=>{expect(s.getIcon).toHaveBeenCalledWith("test",s.sizes.small,null,s.states.default,s.markupIdentifiers.default)}),xit("works get icon from remote server")}),describe("tests for Icons::putInCache",()=>{it("works for simply identifier and markup",()=>{const e=new Promise(e=>e());s.putInPromiseCache("foo",e),expect(s.getFromPromiseCache("foo")).toBe(e),expect(s.isPromiseCached("foo")).toBe(!0)})}),describe("tests for Icons::getFromPromiseCache",()=>{it("return undefined for uncached promise",()=>{expect(s.getFromPromiseCache("bar")).not.toBeDefined(),expect(s.isPromiseCached("bar")).toBe(!1)})})})}));