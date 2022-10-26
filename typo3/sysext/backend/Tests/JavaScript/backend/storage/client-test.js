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
import Client from"@typo3/backend/storage/client.js";describe("@typo3/backend/storage/client",(()=>{afterEach((()=>{Client.clear()})),it("can set and get item",(()=>{const e="test-key";Client.set(e,"foo"),expect(Client.get(e)).toBe("foo")})),it("can check if item is set",(()=>{const e="test-key";expect(Client.isset(e)).toBeFalse(),Client.set(e,"foo"),expect(Client.isset(e)).toBeTrue()})),it("can get multiple items by prefix",(()=>{const e={"test-prefix-foo":"foo","test-prefix-bar":"bar","test-prefix-baz":"baz"};for(const[t,i]of Object.entries(e))Client.set(t,i);const t=Client.getByPrefix("test-prefix-");expect(t).toEqual(e)})),it("can remove item",(()=>{const e="item-to-be-removed";Client.set(e,"foo"),expect(Client.get(e)).not.toBeNull(),Client.unset(e),expect(Client.get(e)).toBeNull()})),it("can remove multiple items by prefix",(()=>{const e={"test-prefix-foo":"foo","test-prefix-bar":"bar","test-prefix-baz":"baz"};for(const[t,i]of Object.entries(e))Client.set(t,i);Client.unsetByPrefix("test-prefix-");const t=Client.getByPrefix("test-prefix-");expect(t).toHaveSize(0)})),it("can clear storage",(()=>{const e={foo:"foo",baz:"bencer",huselpusel:"42"};for(const[t,i]of Object.entries(e))Client.set(t,i);Client.clear(),expect(localStorage.length).toHaveSize(0)}))}));