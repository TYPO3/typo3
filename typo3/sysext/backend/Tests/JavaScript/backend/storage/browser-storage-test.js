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
import BrowserSession from"@typo3/backend/storage/browser-session.js";describe("@typo3/backend/storage/browser-session",(()=>{afterEach((()=>{BrowserSession.clear()})),it("can set and get item",(()=>{const e="test-key";BrowserSession.set(e,"foo"),expect(BrowserSession.get(e)).toBe("foo")})),it("can check if item is set",(()=>{const e="test-key";expect(BrowserSession.isset(e)).toBeFalse(),BrowserSession.set(e,"foo"),expect(BrowserSession.isset(e)).toBeTrue()})),it("can get multiple items by prefix",(()=>{const e={"test-prefix-foo":"foo","test-prefix-bar":"bar","test-prefix-baz":"baz"};for(const[s,o]of Object.entries(e))BrowserSession.set(s,o);const s=BrowserSession.getByPrefix("test-prefix-");expect(s).toEqual(e)})),it("can remove item",(()=>{const e="item-to-be-removed";BrowserSession.set(e,"foo"),expect(BrowserSession.get(e)).not.toBeNull(),BrowserSession.unset(e),expect(BrowserSession.get(e)).toBeNull()})),it("can remove multiple items by prefix",(()=>{const e={"test-prefix-foo":"foo","test-prefix-bar":"bar","test-prefix-baz":"baz"};for(const[s,o]of Object.entries(e))BrowserSession.set(s,o);BrowserSession.unsetByPrefix("test-prefix-");const s=BrowserSession.getByPrefix("test-prefix-");expect(s).toHaveSize(0)})),it("can clear storage",(()=>{const e={foo:"foo",baz:"bencer",huselpusel:"42"};for(const[s,o]of Object.entries(e))BrowserSession.set(s,o);BrowserSession.clear(),expect(sessionStorage.length).toHaveSize(0)}))}));