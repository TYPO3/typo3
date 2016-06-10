define(['jquery', 'TYPO3/CMS/Backend/Icons'], function($, Icons) {
	'use strict';

	describe('TYPO3/CMS/Backend/IconsTest:', function() {
		/**
		 * @test
		 */
		describe('tests for Icons object', function() {
			it('has all sizes', function() {
				expect(Icons.sizes.small).toBe('small');
				expect(Icons.sizes.default).toBe('default');
				expect(Icons.sizes.large).toBe('large');
				expect(Icons.sizes.overlay).toBe('overlay');
			});
			it('has all states', function() {
				expect(Icons.states.default).toBe('default');
				expect(Icons.states.disabled).toBe('disabled');
			});
			it('has all markupIdentifiers', function() {
				expect(Icons.markupIdentifiers.default).toBe('default');
				expect(Icons.markupIdentifiers.inline).toBe('inline');
			});
		});

		/**
		 * @test
		 */
		describe('tests for Icons::getIcon', function() {
			beforeEach(function() {
				spyOn(Icons, 'getIcon');
				Icons.getIcon('test', Icons.sizes.small, null, Icons.states.default, Icons.markupIdentifiers.default);
			});

			it("tracks that the spy was called", function() {
				expect(Icons.getIcon).toHaveBeenCalled();
			});
			it("tracks all the arguments of its calls", function() {
				expect(Icons.getIcon).toHaveBeenCalledWith('test', Icons.sizes.small, null, Icons.states.default, Icons.markupIdentifiers.default);
			});
			xit('works get icon from remote server');
		});

		/**
		 * @test
		 */
		describe('tests for Icons::putInCache', function() {
			it('works for simply identifier and markup', function() {
				Icons.putInCache('foo', 'bar');
				expect(Icons.cache['foo']).toBe('bar');
			});
		});

		/**
		 * @test
		 */
		describe('tests for Icons::getFromCache', function() {
			it('return undefined for uncached icon', function() {
				expect(Icons.getFromCache('bar')).not.toBeDefined();
			});
		});

		/**
		 * @test
		 */
		describe('tests for Icons::isCached', function() {
			it('return true for cached icon', function() {
				expect(Icons.isCached('foo')).toBe(true);
			});
			it('return false for uncached icon', function() {
				expect(Icons.isCached('bar')).toBe(false);
			});
		});
	});
});
