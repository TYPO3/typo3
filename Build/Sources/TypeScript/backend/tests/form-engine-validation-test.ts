import FormEngineValidation from '@typo3/backend/form-engine-validation.js';
import { expect } from '@open-wc/testing';

interface FormEngineConfig {
  [x: string]: any;
  field: string;
}

interface FormatValueData {
  description: string,
  type: string,
  value: string,
  result: string
}

interface FormatInvalidValueData {
  description: string,
  type: string,
  value: number|string,
}

interface ProcessValueData {
  description: string,
  command: string,
  value: string,
  config: FormEngineConfig,
  result: string
}

//type Values = Array<object>|object;
type Values = any;

/**
 * Helper function to implement DataProvider
 * @param {Function|Array|Object} values
 * @param {Function} func
 */
function using(values: (() => Values)|Values, func: (...args: any[]) => void): void {
  if (values instanceof Function) {
    values = values();
  }

  if (values instanceof Array) {
    values.forEach(function(value) {
      if (!(value instanceof Array)) {
        value = [value];
      }

      func(...value);
    });
  } else {
    const objectKeys = Object.keys(values);

    objectKeys.forEach(function(key) {
      if (!(values[key] instanceof Array)) {
        values[key] = [values[key]];
      }

      values[key].push(key);

      func(...values[key]);
    });
  }
}

describe('TYPO3/CMS/Backend/FormEngineValidationTest:', () => {
  const formatValueDataProvider: Array<FormatValueData> = [
    {
      'description': 'works for type date with iso date',
      'type': 'date',
      'value': '2016-12-02T11:16:06',
      'result': '2016-12-02T11:16:06'
    },
    {
      'description': 'works for type date with iso date with milliseconds',
      'type': 'date',
      'value': '2016-12-02T11:16:06.000',
      'result': '2016-12-02T11:16:06'
    },
    {
      'description': 'works for type date with empty value',
      'type': 'date',
      'value': '',
      'result': ''
    },
    {
      'description': 'works for type datetime with iso date',
      'type': 'datetime',
      'value': '2016-12-02T11:16:06',
      'result': '2016-12-02T11:16:06'
    },
    {
      'description': 'works for type datetime with iso date with milliseconds',
      'type': 'datetime',
      'value': '2016-12-02T11:16:06.000',
      'result': '2016-12-02T11:16:06'
    },
    {
      'description': 'works for type datetime with empty value',
      'type': 'datetime',
      'value': '',
      'result': ''
    },
    {
      'description': 'works for type time with iso date',
      'type': 'time',
      'value': '2016-12-02T11:16:06',
      'result': '2016-12-02T11:16:06'
    },
    {
      'description': 'works for type time with empty value',
      'type': 'time',
      'value': '',
      'result': ''
    },
    {
      'description': 'works for type timesec with iso date',
      'type': 'timesec',
      'value': '2016-12-02T11:16:06',
      'result': '2016-12-02T11:16:06'
    },
    {
      'description': 'works for type timesec with empty value',
      'type': 'timesec',
      'value': '',
      'result': ''
    },
  ];

  describe('tests for formatValue', () => {
    using(formatValueDataProvider, function(testCase: FormatValueData) {
      it(testCase.description, () => {
        const result = FormEngineValidation.formatValue(testCase.type, testCase.value);
        expect(result).to.equal(testCase.result);
      });
    });
  });

  const formatInvalidValueDataProvider: Array<FormatInvalidValueData> = [
    {
      'description': 'throws error with int 0',
      'type': 'date',
      'value': 0,
    },
    {
      'description': 'throws error for type date with timestamp',
      'type': 'date',
      'value': 10000000,
    },
    {
      'description': 'throws error with non-iso date',
      'type': 'date',
      'value': 'foo',
    },
    {
      'description': 'throws error for type datetime with timestamp',
      'type': 'datetime',
      'value': 10000000,
    },
    {
      'description': 'throws error for invalid value',
      'type': 'datetime',
      'value': 'invalid',
    },
    {
      'description': 'throws error for type time with timestamp',
      'type': 'time',
      'value': 10000000,
    },
  ];

  describe('tests for invalid value to formatValue', () => {
    using(formatInvalidValueDataProvider, function(testCase: FormatInvalidValueData) {
      it(testCase.description, () => {
        expect(() => FormEngineValidation.formatValue(testCase.type, testCase.value)).to.throw();
      });
    });
  });

  const processValueDataProvider: Array<ProcessValueData> = [
    {
      'description': 'works for command alpha with numeric value',
      'command': 'alpha',
      'value': '1234',
      'config': { 'field': 'foo' },
      'result': ''
    },
    {
      'description': 'works for command alpha with string value',
      'command': 'alpha',
      'value': 'abc',
      'config': { 'field': 'foo' },
      'result': 'abc'
    },
    {
      'description': 'works for command alpha with alphanum input',
      'command': 'alpha',
      'value': 'abc123',
      'config': { 'field': 'foo' },
      'result': 'abc'
    },
    {
      'description': 'works for command alpha with alphanum input',
      'command': 'alpha',
      'value': '123abc123',
      'config': { 'field': 'foo' },
      'result': 'abc'
    },
    {
      'description': 'works for command lower with mixed case value',
      'command': 'lower',
      'value': 'Editor@Example.ORG',
      'config': { 'field': 'foo' },
      'result': 'editor@example.org'
    },
    {
      'description': 'works for command integer with numeric value',
      'command': 'integer',
      'value': '1234',
      'config': { 'field': 'foo' },
      'result': '1234'
    }
  ];

  describe('test for processValue', () => {
    using(processValueDataProvider, function(testCase: ProcessValueData) {
      it(testCase.description, () => {
        const result = FormEngineValidation.processValue(testCase.command, testCase.value, testCase.config);
        expect(result).to.equal(testCase.result);
      });
    });
  });

  describe('tests for parseInt', () => {
    it('works for value 0', () => {
      expect(FormEngineValidation.parseInt(0)).to.equal(0);
    });
    it('works for value 1', () => {
      expect(FormEngineValidation.parseInt(1)).to.equal(1);
    });
    it('works for value -1', () => {
      expect(FormEngineValidation.parseInt(-1)).to.equal(-1);
    });
    it('works for value "0"', () => {
      expect(FormEngineValidation.parseInt('0')).to.equal(0);
    });
    it('works for value "1"', () => {
      expect(FormEngineValidation.parseInt('1')).to.equal(1);
    });
    it('works for value "-1"', () => {
      expect(FormEngineValidation.parseInt('-1')).to.equal(-1);
    });
    it('works for value 0.5', () => {
      expect(FormEngineValidation.parseInt(0.5)).to.equal(0);
    });
    it('works for value "0.5"', () => {
      expect(FormEngineValidation.parseInt('0.5')).to.equal(0);
    });
    it('works for value "foo"', () => {
      expect(FormEngineValidation.parseInt('foo')).to.equal(0);
    });
    it('works for value true', () => {
      expect(FormEngineValidation.parseInt(true)).to.equal(0);
    });
    it('works for value false', () => {
      expect(FormEngineValidation.parseInt(false)).to.equal(0);
    });
    it('works for value null', () => {
      expect(FormEngineValidation.parseInt(null)).to.equal(0);
    });
  });

  describe('tests for parseDouble', () => {
    it('works for value 0', () => {
      expect(FormEngineValidation.parseDouble(0)).to.equal('0.00');
    });
    it('works for value 1', () => {
      expect(FormEngineValidation.parseDouble(1)).to.equal('1.00');
    });
    it('works for value -1', () => {
      expect(FormEngineValidation.parseDouble(-1)).to.equal('-1.00');
    });
    it('works for value "0"', () => {
      expect(FormEngineValidation.parseDouble('0')).to.equal('0.00');
    });
    it('works for value "1"', () => {
      expect(FormEngineValidation.parseDouble('1')).to.equal('1.00');
    });
    it('works for value "-1"', () => {
      expect(FormEngineValidation.parseDouble('-1')).to.equal('-1.00');
    });
    it('works for value 0.5', () => {
      expect(FormEngineValidation.parseDouble(0.5)).to.equal('0.50');
    });
    it('works for value "0.5"', () => {
      expect(FormEngineValidation.parseDouble('0.5')).to.equal('0.50');
    });
    it('works for value "foo"', () => {
      expect(FormEngineValidation.parseDouble('foo')).to.equal('0.00');
    });
    it('works for value true', () => {
      expect(FormEngineValidation.parseDouble(true)).to.equal('0.00');
    });
    it('works for value false', () => {
      expect(FormEngineValidation.parseDouble(false)).to.equal('0.00');
    });
    it('works for value null', () => {
      expect(FormEngineValidation.parseDouble(null)).to.equal('0.00');
    });
    it('works for value 0.5 with scale 4', () => {
      expect(FormEngineValidation.parseDouble(0.5, 4)).to.equal('0.5000');
    });
    it('works for value "1.23456" with scale 4', () => {
      expect(FormEngineValidation.parseDouble('1.23456', 4)).to.equal('1.2346');
    });
    it('works for value "1.23456" with scale 1', () => {
      expect(FormEngineValidation.parseDouble('1.23456', 1)).to.equal('1.2');
    });
    it('keeps the exact digits with scale 30', () => {
      expect(FormEngineValidation.parseDouble('2.000000001', 30)).to.equal('2.000000001000000000000000000000');
    });
    it('keeps all given digits with scale 30', () => {
      expect(FormEngineValidation.parseDouble('2.000000001000000082740370999091', 30)).to.equal('2.000000001000000082740370999091');
    });
    it('carries the rounding over to the integer digits', () => {
      expect(FormEngineValidation.parseDouble('1.999', 2)).to.equal('2.00');
    });
    it('rounds up to an integer digit', () => {
      expect(FormEngineValidation.parseDouble('0.999', 2)).to.equal('1.00');
    });
    it('does not return a negative zero', () => {
      expect(FormEngineValidation.parseDouble('-0.001', 2)).to.equal('0.00');
    });
    it('rounds a negative value away from zero', () => {
      expect(FormEngineValidation.parseDouble('-1.005', 2)).to.equal('-1.01');
    });
  });

  describe('tests for updateLabelTitle', () => {
    const createLabel = (...classes: string[]): HTMLElement => {
      const label = document.createElement('label');
      label.classList.add('t3js-formengine-label', ...classes);
      return label;
    };

    it('adds no title to a label without state', () => {
      const label = createLabel();
      FormEngineValidation.updateLabelTitle(label);
      expect(label.hasAttribute('title')).to.be.false;
    });
    it('describes an invalid field', () => {
      const label = createLabel('has-error');
      FormEngineValidation.updateLabelTitle(label);
      expect(label.getAttribute('title')).to.equal('labels.fieldNotCorrectlyFilledIn');
    });
    it('describes a changed field', () => {
      const label = createLabel('has-change');
      FormEngineValidation.updateLabelTitle(label);
      expect(label.getAttribute('title')).to.equal('labels.fieldHasUnsavedChanges');
    });
    it('prefers the invalid state over the changed state', () => {
      const label = createLabel('has-change', 'has-error');
      FormEngineValidation.updateLabelTitle(label);
      expect(label.getAttribute('title')).to.equal('labels.fieldNotCorrectlyFilledIn');
    });
    it('removes an obsolete title', () => {
      const label = createLabel('has-error');
      FormEngineValidation.updateLabelTitle(label);
      label.classList.remove('has-error');
      FormEngineValidation.updateLabelTitle(label);
      expect(label.hasAttribute('title')).to.be.false;
    });
  });

  describe('tests for updateTabValidationState', () => {
    const createTabLink = (): HTMLElement => {
      const tabLink = document.createElement('button');
      tabLink.textContent = 'General';
      return tabLink;
    };

    it('adds a hidden hint to a tab containing invalid fields', () => {
      const tabLink = createTabLink();
      FormEngineValidation.updateTabValidationState(tabLink, false);
      const hint = tabLink.querySelector('.t3js-validation-hint');
      expect(hint).to.not.be.null;
      expect(hint.classList.contains('visually-hidden')).to.be.true;
      expect(hint.textContent).to.equal(' labels.tabContainsInvalidEntries');
    });
    it('keeps the visible label of the tab untouched', () => {
      const tabLink = createTabLink();
      FormEngineValidation.updateTabValidationState(tabLink, false);
      expect(tabLink.firstChild.textContent).to.equal('General');
    });
    it('adds the hint only once', () => {
      const tabLink = createTabLink();
      FormEngineValidation.updateTabValidationState(tabLink, false);
      FormEngineValidation.updateTabValidationState(tabLink, false);
      expect(tabLink.querySelectorAll('.t3js-validation-hint').length).to.equal(1);
    });
    it('adds no hint to a valid tab', () => {
      const tabLink = createTabLink();
      FormEngineValidation.updateTabValidationState(tabLink, true);
      expect(tabLink.querySelector('.t3js-validation-hint')).to.be.null;
    });
    it('removes an obsolete hint', () => {
      const tabLink = createTabLink();
      FormEngineValidation.updateTabValidationState(tabLink, false);
      FormEngineValidation.updateTabValidationState(tabLink, true);
      expect(tabLink.querySelector('.t3js-validation-hint')).to.be.null;
    });
  });
});
