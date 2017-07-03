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
define(["require", "exports", "TYPO3/CMS/Backend/BackendException"], function (require, exports, BackendException_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    describe('TYPO3/CMS/Backend/BackendException', function () {
        it('sets exception message', function () {
            var backendException = new BackendException_1.BackendException('some message');
            expect(backendException.message).toBe('some message');
        });
        it('sets exception code', function () {
            var backendException = new BackendException_1.BackendException('', 12345);
            expect(backendException.code).toBe(12345);
        });
    });
});
