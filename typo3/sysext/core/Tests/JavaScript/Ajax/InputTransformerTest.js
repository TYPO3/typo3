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
define(["require","exports","TYPO3/CMS/Core/Ajax/InputTransformer"],(function(a,r,e){"use strict";Object.defineProperty(r,"__esModule",{value:!0}),describe("TYPO3/CMS/Core/Ajax/InputTransformer",()=>{it("converts object to FormData",()=>{const a=new FormData;a.set("foo","bar"),a.set("bar","baz"),a.set("nested[works]","yes"),expect(e.InputTransformer.toFormData({foo:"bar",bar:"baz",nested:{works:"yes"}})).toEqual(a)}),it("undefined values are removed in FormData",()=>{const a={foo:"bar",bar:"baz",removeme:void 0},r=new FormData;r.set("foo","bar"),r.set("bar","baz"),expect(e.InputTransformer.toFormData(a)).toEqual(r)}),it("converts object to SearchParams",()=>{expect(e.InputTransformer.toSearchParams({foo:"bar",bar:"baz",nested:{works:"yes"}})).toEqual("foo=bar&bar=baz&nested[works]=yes")}),it("merges array to SearchParams",()=>{expect(e.InputTransformer.toSearchParams(["foo=bar","bar=baz"])).toEqual("foo=bar&bar=baz")}),it("keeps string in SearchParams",()=>{expect(e.InputTransformer.toSearchParams("foo=bar&bar=baz")).toEqual("foo=bar&bar=baz")}),it("undefined values are removed in SearchParams",()=>{const a={foo:"bar",bar:"baz",removeme:void 0};expect(e.InputTransformer.toSearchParams(a)).toEqual("foo=bar&bar=baz")})})}));