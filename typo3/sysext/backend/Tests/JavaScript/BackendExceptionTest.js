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
