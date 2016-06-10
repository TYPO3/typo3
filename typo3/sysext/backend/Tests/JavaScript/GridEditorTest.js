define(['jquery', 'TYPO3/CMS/Backend/GridEditor'], function($, GridEditor) {
	'use strict';

	describe('TYPO3/CMS/Backend/GridEditorTest:', function() {
		/**
		 * @test
		 */
		describe('tests for getNewCell', function() {
			it('works and return a default cell object', function() {
				var cell = {
					spanned: 0,
					rowspan: 1,
					colspan: 1,
					name: '',
					colpos: ''
				};
				expect(GridEditor.getNewCell()).toEqual(cell);
			});
		});

		/**
		 * @test
		 */
		describe('tests for addRow', function() {
			var origData = GridEditor.data;
			it('works and add a new row', function() {
				//GridEditor.addRow();
				//expect(GridEditor.data.length).toBe(origData.length + 1);
				pending('TypeError: undefined is not an object (evaluating GridEditor.data.push)');
			});
		});

		/**
		 * @test
		 */
		describe('tests for stripMarkup', function() {
			it('works with string which contains html markup only', function() {
				expect(GridEditor.stripMarkup('<b>foo</b>')).toBe('');
			});
			it('works with string which contains html markup and normal text', function() {
				expect(GridEditor.stripMarkup('<b>foo</b> bar')).toBe(' bar');
			});
		});
	});
});
