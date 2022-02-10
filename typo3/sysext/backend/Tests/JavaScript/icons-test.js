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
import Icons from"@typo3/backend/icons.js";describe("TYPO3/CMS/Backend/IconsTest:",()=>{describe("tests for Icons object",()=>{it("has all sizes",()=>{expect(Icons.sizes.small).toBe("small"),expect(Icons.sizes.default).toBe("default"),expect(Icons.sizes.large).toBe("large"),expect(Icons.sizes.overlay).toBe("overlay")}),it("has all states",()=>{expect(Icons.states.default).toBe("default"),expect(Icons.states.disabled).toBe("disabled")}),it("has all markupIdentifiers",()=>{expect(Icons.markupIdentifiers.default).toBe("default"),expect(Icons.markupIdentifiers.inline).toBe("inline")})}),describe("tests for Icons::getIcon",()=>{beforeEach(()=>{spyOn(Icons,"getIcon"),Icons.getIcon("test",Icons.sizes.small,null,Icons.states.default,Icons.markupIdentifiers.default)}),it("tracks that the spy was called",()=>{expect(Icons.getIcon).toHaveBeenCalled()}),it("tracks all the arguments of its calls",()=>{expect(Icons.getIcon).toHaveBeenCalledWith("test",Icons.sizes.small,null,Icons.states.default,Icons.markupIdentifiers.default)}),xit("works get icon from remote server")}),describe("tests for Icons::putInCache",()=>{it("works for simply identifier and markup",()=>{const e=new Promise(e=>e());Icons.putInPromiseCache("foo",e),expect(Icons.getFromPromiseCache("foo")).toBe(e),expect(Icons.isPromiseCached("foo")).toBe(!0)})}),describe("tests for Icons::getFromPromiseCache",()=>{it("return undefined for uncached promise",()=>{expect(Icons.getFromPromiseCache("bar")).not.toBeDefined(),expect(Icons.isPromiseCached("bar")).toBe(!1)})})});