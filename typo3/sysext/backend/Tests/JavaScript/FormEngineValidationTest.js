define(['jquery', 'TYPO3/CMS/Backend/FormEngineValidation'], function($, FormEngineValidation) {
	'use strict';

	describe('TYPO3/CMS/Backend/FormEngineValidationTest:', function() {
		/**
		 * @type {*[]}
		 */
		var formatValueDataProvider = [
			{
				'description': 'works for type date',
				'type': 'date',
				'value': 0,
				'config': [],
				'result': ''
			},
			{
				'description': 'works for type date with timestamp',
				'type': 'date',
				'value': 10000000,
				'config': [],
				'result': '26-4-1970'
			},
			{
				'description': 'works for type datetime',
				'type': 'datetime',
				'value': 0,
				'config': [],
				'result': ''
			},
			{
				'description': 'works for type datetime with timestamp',
				'type': 'datetime',
				'value': 10000000,
				'config': [],
				'result': '17:46 26-4-1970'
			},
			{
				'description': 'works for type time',
				'type': 'time',
				'value': 0,
				'config': [],
				'result': '0:00'
			},
			{
				'description': 'works for type time with timestamp',
				'type': 'time',
				'value': 10000000,
				'config': [],
				'result': '17:46'
			}
		];

		/**
		 * @dataProvider formatValueDataProvider
		 * @test
		 */
		describe('tests for formatValue', function() {
			using(formatValueDataProvider, function(testCase) {
				it(testCase.description, function() {
					var result = FormEngineValidation.formatValue(testCase.type, testCase.value, testCase.config);
					expect(result).toBe(testCase.result);
				});
			});
		});

		/**
		 * @type {*[]}
		 */
		var processValueDataProvider = [
			{
				'description': 'works for command alpha with numeric value',
				'command': 'alpha',
				'value': '1234',
				'config': [],
				'result': ''
			},
			{
				'description': 'works for command alpha with string value',
				'command': 'alpha',
				'value': 'abc',
				'config': [],
				'result': 'abc'
			},
			{
				'description': 'works for command alpha with alphanum input',
				'command': 'alpha',
				'value': 'abc123',
				'config': [],
				'result': 'abc'
			},
			{
				'description': 'works for command alpha with alphanum input',
				'command': 'alpha',
				'value': '123abc123',
				'config': [],
				'result': 'abc'
			}
		];

		/**
		 * @dataProvider processValueDataProvider
		 * @test
		 */
		describe('test for processValue', function() {
			using(processValueDataProvider, function(testCase) {
				it(testCase.description, function() {
					var result = FormEngineValidation.processValue(testCase.command, testCase.value, testCase.config);
					expect(result).toBe(testCase.result);
				});
			});
		});

		/**
		 * @test
		 */
		xdescribe('tests for validateField');

		/**
		 * @test
		 */
		describe('tests for trimExplode', function () {
			it('works for comma as separator and list without spaces', function () {
				expect(FormEngineValidation.trimExplode(',', 'foo,bar,baz')).toEqual(['foo', 'bar', 'baz']);
			});
			it('works for comma as separator and list with spaces', function () {
				expect(FormEngineValidation.trimExplode(',', ' foo , bar , baz ')).toEqual(['foo', 'bar', 'baz']);
			});
			it('works for pipe as separator and list with spaces', function () {
				expect(FormEngineValidation.trimExplode('|', ' foo | bar | baz ')).toEqual(['foo', 'bar', 'baz']);
			});
		});

		/**
		 * @test
		 */
		describe('tests for parseInt', function() {
			it('works for value 0', function () {
				expect(FormEngineValidation.parseInt(0)).toBe(0);
			});
			it('works for value 1', function () {
				expect(FormEngineValidation.parseInt(1)).toBe(1);
			});
			it('works for value -1', function () {
				expect(FormEngineValidation.parseInt(-1)).toBe(-1);
			});
			it('works for value "0"', function () {
				expect(FormEngineValidation.parseInt('0')).toBe(0);
			});
			it('works for value "1"', function () {
				expect(FormEngineValidation.parseInt('1')).toBe(1);
			});
			it('works for value "-1"', function () {
				expect(FormEngineValidation.parseInt('-1')).toBe(-1);
			});
			it('works for value 0.5', function () {
				expect(FormEngineValidation.parseInt(0.5)).toBe(0);
			});
			it('works for value "0.5"', function () {
				expect(FormEngineValidation.parseInt('0.5')).toBe(0);
			});
			it('works for value "foo"', function () {
				expect(FormEngineValidation.parseInt('foo')).toBe(0);
			});
			it('works for value true', function () {
				expect(FormEngineValidation.parseInt(true)).toBe(0);
			});
			it('works for value false', function () {
				expect(FormEngineValidation.parseInt(false)).toBe(0);
			});
			it('works for value null', function () {
				expect(FormEngineValidation.parseInt(null)).toBe(0);
			});
		});
		/**
		 * @test
		 */
		describe('tests for parseDouble', function() {
			it('works for value 0', function () {
				expect(FormEngineValidation.parseDouble(0)).toBe('0.00');
			});
			it('works for value 1', function () {
				expect(FormEngineValidation.parseDouble(1)).toBe('1.00');
			});
			it('works for value -1', function () {
				expect(FormEngineValidation.parseDouble(-1)).toBe('-1.00');
			});
			it('works for value "0"', function () {
				expect(FormEngineValidation.parseDouble('0')).toBe('0.00');
			});
			it('works for value "1"', function () {
				expect(FormEngineValidation.parseDouble('1')).toBe('1.00');
			});
			it('works for value "-1"', function () {
				expect(FormEngineValidation.parseDouble('-1')).toBe('-1.00');
			});
			it('works for value 0.5', function () {
				expect(FormEngineValidation.parseDouble(0.5)).toBe('0.50');
			});
			it('works for value "0.5"', function () {
				expect(FormEngineValidation.parseDouble('0.5')).toBe('0.50');
			});
			it('works for value "foo"', function () {
				expect(FormEngineValidation.parseDouble('foo')).toBe('0.00');
			});
			it('works for value true', function () {
				expect(FormEngineValidation.parseDouble(true)).toBe('0.00');
			});
			it('works for value false', function () {
				expect(FormEngineValidation.parseDouble(false)).toBe('0.00');
			});
			it('works for value null', function () {
				expect(FormEngineValidation.parseDouble(null)).toBe('0.00');
			});
		});

		/**
		 * @test
		 */
		describe('tests for btrim', function() {
			var result = FormEngineValidation.btrim(' test ');

			it('works for string with whitespace in begin and end', function() {
				expect(result).toBe(' test');
			});
		});

		/**
		 * @test
		 */
		describe('tests for ltrim', function() {
			var result = FormEngineValidation.ltrim(' test ');

			it('works for string with whitespace in begin and end', function() {
				expect(result).toBe('test ');
			});
		});

		/**
		 * @test
		 */
		xdescribe('tests for parseDateTime');

		/**
		 * @test
		 */
		xdescribe('tests for parseDate');

		/**
		 * @test
		 */
		xdescribe('tests for parseTime');

		/**
		 * @test
		 */
		xdescribe('tests for parseYear');

		/**
		 * @test
		 */
		describe('tests for getYear', function () {
			var currentDate = new Date();
			afterEach(function() {
				jasmine.clock().mockDate(currentDate);
			});

			it('works for current date', function () {
				var date = new Date();
				expect(FormEngineValidation.getYear(date)).toBe(date.getYear() + 1900);
			});
			it('works for year 2013', function () {
				var baseTime = new Date(2013, 9, 23);
				jasmine.clock().mockDate(baseTime);
				expect(FormEngineValidation.getYear(baseTime)).toBe(2013);
			})
		});

		/**
		 * @test
		 */
		describe('tests for getDate', function () {
			var currentDate = new Date();
			afterEach(function() {
				jasmine.clock().mockDate(currentDate);
			});

			xit('works for year 2013', function () {
				var baseTime = new Date(2013, 9, 23, 13, 13, 13);
				jasmine.clock().mockDate(baseTime);
				expect(FormEngineValidation.getDate(baseTime)).toBe(1382479200);
			})
		});

		/**
		 * @test
		 */
		describe('tests for splitStr', function () {
			it('works for command and index', function () {
				expect(FormEngineValidation.splitStr('foo,bar,baz', ',', -1)).toBe('foo');
				expect(FormEngineValidation.splitStr('foo,bar,baz', ',', 0)).toBe('foo');
				expect(FormEngineValidation.splitStr('foo,bar,baz', ',', 1)).toBe('foo');
				expect(FormEngineValidation.splitStr('foo,bar,baz', ',', 2)).toBe('bar');
				expect(FormEngineValidation.splitStr('foo,bar,baz', ',', 3)).toBe('baz');
				expect(FormEngineValidation.splitStr(' foo , bar , baz ', ',', 1)).toBe(' foo ');
				expect(FormEngineValidation.splitStr(' foo , bar , baz ', ',', 2)).toBe(' bar ');
				expect(FormEngineValidation.splitStr(' foo , bar , baz ', ',', 3)).toBe(' baz ');
			});
		});

		/**
		 * @test
		 */
		xdescribe('tests for split');
	});
});
