import $ from 'jquery';
import FormEngineValidation from '@typo3/backend/form-engine-validation';

declare function using(values: Function|Array<Object>|Object, func: Function): void;

interface FormEngineConfig {
  [x: string]: any;
}

interface FormatValueData {
  description: string,
  type: string,
  value: number|string,
  config: FormEngineConfig,
  result: string
}

interface ProcessValueData {
  description: string,
  command: string,
  value: string,
  config: FormEngineConfig,
  result: string
}

describe('TYPO3/CMS/Backend/FormEngineValidationTest:', () => {
  const formatValueDataProvider: Array<FormatValueData> = [
    {
      'description': 'works for type date',
      'type': 'date',
      'value': 0,
      'config': {},
      'result': ''
    },
    {
      'description': 'works for type date with timestamp',
      'type': 'date',
      'value': 10000000,
      'config': {},
      'result': '26-04-1970'
    },
    {
      'description': 'works for type date with iso date',
      'type': 'date',
      'value': '2016-12-02T11:16:06+00:00',
      'config': {},
      'result': '02-12-2016'
    },
    {
      'description': 'works for type datetime',
      'type': 'datetime',
      'value': 0,
      'config': {},
      'result': ''
    },
    {
      'description': 'works for type datetime with timestamp',
      'type': 'datetime',
      'value': 10000000,
      'config': {},
      'result': '17:46 26-04-1970'
    },
    {
      'description': 'works for type datetime with iso date',
      'type': 'datetime',
      'value': '2016-12-02T11:16:06+00:00',
      'config': {},
      'result': '11:16 02-12-2016'
    },
    {
      'description': 'resolves to empty result for zero value',
      'type': 'datetime',
      'value': 0,
      'config': {},
      'result': ''
    },
    {
      'description': 'resolves to empty result for invalid value',
      'type': 'datetime',
      'value': 'invalid',
      'config': {},
      'result': ''
    },
    {
      'description': 'works for type time',
      'type': 'time',
      'value': 0,
      'config': {},
      'result': '00:00'
    },
    {
      'description': 'works for type time with timestamp',
      'type': 'time',
      'value': 10000000,
      'config': {},
      'result': '17:46'
    },
    {
      'description': 'works for type time with iso date',
      'type': 'time',
      'value': '2016-12-02T11:16:06+00:00',
      'config': {},
      'result': '11:16'
    }
  ];

  /**
   * @dataProvider formatValueDataProvider
   * @test
   */
  describe('tests for formatValue', () => {
    using(formatValueDataProvider, function(testCase: FormatValueData) {
      it(testCase.description, () => {
        FormEngineValidation.initialize();
        const result = FormEngineValidation.formatValue(testCase.type, testCase.value, testCase.config);
        expect(result).toBe(testCase.result);
      });
    });
  });

  const processValueDataProvider: Array<ProcessValueData> = [
    {
      'description': 'works for command alpha with numeric value',
      'command': 'alpha',
      'value': '1234',
      'config': {},
      'result': ''
    },
    {
      'description': 'works for command alpha with string value',
      'command': 'alpha',
      'value': 'abc',
      'config': {},
      'result': 'abc'
    },
    {
      'description': 'works for command alpha with alphanum input',
      'command': 'alpha',
      'value': 'abc123',
      'config': {},
      'result': 'abc'
    },
    {
      'description': 'works for command alpha with alphanum input',
      'command': 'alpha',
      'value': '123abc123',
      'config': {},
      'result': 'abc'
    }
  ];

  /**
   * @dataProvider processValueDataProvider
   * @test
   */
  describe('test for processValue', () => {
    using(processValueDataProvider, function(testCase: ProcessValueData) {
      it(testCase.description, () => {
        const result = FormEngineValidation.processValue(testCase.command, testCase.value, testCase.config);
        expect(result).toBe(testCase.result);
      });
    });
  });

  /**
   * @test
   */
  xdescribe('tests for validateField', () => {
    /* tslint:disable:no-empty */
  });

  /**
   * @test
   */
  describe('tests for trimExplode', () => {
    it('works for comma as separator and list without spaces', () => {
      expect(FormEngineValidation.trimExplode(',', 'foo,bar,baz')).toEqual(['foo', 'bar', 'baz']);
    });
    it('works for comma as separator and list with spaces', () => {
      expect(FormEngineValidation.trimExplode(',', ' foo , bar , baz ')).toEqual(['foo', 'bar', 'baz']);
    });
    it('works for pipe as separator and list with spaces', () => {
      expect(FormEngineValidation.trimExplode('|', ' foo | bar | baz ')).toEqual(['foo', 'bar', 'baz']);
    });
  });

  /**
   * @test
   */
  describe('tests for parseInt', () => {
    it('works for value 0', () => {
      expect(FormEngineValidation.parseInt(0)).toBe(0);
    });
    it('works for value 1', () => {
      expect(FormEngineValidation.parseInt(1)).toBe(1);
    });
    it('works for value -1', () => {
      expect(FormEngineValidation.parseInt(-1)).toBe(-1);
    });
    it('works for value "0"', () => {
      expect(FormEngineValidation.parseInt('0')).toBe(0);
    });
    it('works for value "1"', () => {
      expect(FormEngineValidation.parseInt('1')).toBe(1);
    });
    it('works for value "-1"', () => {
      expect(FormEngineValidation.parseInt('-1')).toBe(-1);
    });
    it('works for value 0.5', () => {
      expect(FormEngineValidation.parseInt(0.5)).toBe(0);
    });
    it('works for value "0.5"', () => {
      expect(FormEngineValidation.parseInt('0.5')).toBe(0);
    });
    it('works for value "foo"', () => {
      expect(FormEngineValidation.parseInt('foo')).toBe(0);
    });
    it('works for value true', () => {
      expect(FormEngineValidation.parseInt(true)).toBe(0);
    });
    it('works for value false', () => {
      expect(FormEngineValidation.parseInt(false)).toBe(0);
    });
    it('works for value null', () => {
      expect(FormEngineValidation.parseInt(null)).toBe(0);
    });
  });

  /**
   * @test
   */
  describe('tests for parseDouble', () => {
    it('works for value 0', () => {
      expect(FormEngineValidation.parseDouble(0)).toBe('0.00');
    });
    it('works for value 1', () => {
      expect(FormEngineValidation.parseDouble(1)).toBe('1.00');
    });
    it('works for value -1', () => {
      expect(FormEngineValidation.parseDouble(-1)).toBe('-1.00');
    });
    it('works for value "0"', () => {
      expect(FormEngineValidation.parseDouble('0')).toBe('0.00');
    });
    it('works for value "1"', () => {
      expect(FormEngineValidation.parseDouble('1')).toBe('1.00');
    });
    it('works for value "-1"', () => {
      expect(FormEngineValidation.parseDouble('-1')).toBe('-1.00');
    });
    it('works for value 0.5', () => {
      expect(FormEngineValidation.parseDouble(0.5)).toBe('0.50');
    });
    it('works for value "0.5"', () => {
      expect(FormEngineValidation.parseDouble('0.5')).toBe('0.50');
    });
    it('works for value "foo"', () => {
      expect(FormEngineValidation.parseDouble('foo')).toBe('0.00');
    });
    it('works for value true', () => {
      expect(FormEngineValidation.parseDouble(true)).toBe('0.00');
    });
    it('works for value false', () => {
      expect(FormEngineValidation.parseDouble(false)).toBe('0.00');
    });
    it('works for value null', () => {
      expect(FormEngineValidation.parseDouble(null)).toBe('0.00');
    });
  });

  /**
   * @test
   */
  describe('tests for btrim', () => {
    const result = FormEngineValidation.btrim(' test ');

    it('works for string with whitespace in begin and end', () => {
      expect(result).toBe(' test');
    });
  });

  /**
   * @test
   */
  describe('tests for ltrim', () => {
    const result = FormEngineValidation.ltrim(' test ');

    it('works for string with whitespace in begin and end', () => {
      expect(result).toBe('test ');
    });
  });

  /**
   * @test
   */
  xdescribe('tests for parseDateTime', () => {
    /* tslint:disable:no-empty */
  });

  /**
   * @test
   */
  xdescribe('tests for parseDate', () => {
    /* tslint:disable:no-empty */
  });

  /**
   * @test
   */
  xdescribe('tests for parseTime', () => {
    /* tslint:disable:no-empty */
  });

  /**
   * @test
   */
  xdescribe('tests for parseYear', () => {
    /* tslint:disable:no-empty */
  });

  /**
   * @test
   */
  describe('tests for getYear', () => {
    const currentDate = new Date();
    afterEach(() => {
      jasmine.clock().mockDate(currentDate);
    });

    it('works for current date', () => {
      const date = new Date();
      expect(FormEngineValidation.getYear(date)).toBe(date.getFullYear());
    });
    it('works for year 2013', () => {
      const baseTime = new Date(2013, 9, 23);
      jasmine.clock().mockDate(baseTime);
      expect(FormEngineValidation.getYear(baseTime)).toBe(2013);
    })
  });

  /**
   * @test
   */
  describe('tests for getDate', () => {
    const currentDate = new Date();
    afterEach(() => {
      jasmine.clock().mockDate(currentDate);
    });

    xit('works for year 2013', () => {
      const baseTime = new Date(2013, 9, 23, 13, 13, 13);
      jasmine.clock().mockDate(baseTime);
      expect(FormEngineValidation.getDate(baseTime)).toBe(1382479200);
    })
  });

  /**
   * @test
   */
  describe('tests for splitStr', () => {
    it('works for command and index', () => {
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
  xdescribe('tests for split', () => {
    /* tslint:disable:no-empty */
  });
});
