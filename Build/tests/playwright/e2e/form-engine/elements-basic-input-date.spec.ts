import { test } from '../../fixtures/setup-fixtures';
import { ElementsBasicInputTestData } from '../../fixtures/form-engine';
import { openElementsBasicEditor } from '../../helper/form-engine-elements';

test.beforeEach(async ({ backend }) => {
  await openElementsBasicEditor(backend, 'inputDateTime');
});

const dbTypeDateEvalDate: ElementsBasicInputTestData[] = [
  {
    label: 'inputdatetime_2',
    inputValue: '2016-01-29',
    expectedValue: '2016-01-29',
    expectedInternalValue: '2016-01-29T00:00:00',
    expectedValueAfterSave: '2016-01-29T00:00:00',
  },
  {
    label: 'inputdatetime_2',
    inputValue: '2016-02-29',
    expectedValue: '2016-02-29',
    expectedInternalValue: '2016-02-29T00:00:00',
    expectedValueAfterSave: '2016-02-29T00:00:00',
  },
];

const dbTypeDateEvalDatetime: ElementsBasicInputTestData[] = [
  {
    label: 'inputdatetime_3',
    inputValue: '2016-01-29 05:23',
    expectedValue: '2016-01-29 05:23',
    expectedInternalValue: '2016-01-29T05:23:00',
    expectedValueAfterSave: '2016-01-29T05:23:00',
  },
  {
    label: 'inputdatetime_3',
    inputValue: '2016-02-29 05:23',
    expectedValue: '2016-02-29 05:23',
    expectedInternalValue: '2016-02-29T05:23:00',
    expectedValueAfterSave: '2016-02-29T05:23:00',
  },
  {
    label: 'inputdatetime_11',
    inputValue: '',
    expectedValue: '',
    expectedInternalValue: '',
    expectedValueAfterSave: '',
  },
  {
    label: 'inputdatetime_23',
    inputValue: '1970-01-01 00:00',
    expectedValue: '1970-01-01 00:00',
    expectedInternalValue: '1970-01-01T00:00:00',
    expectedValueAfterSave: '1970-01-01T00:00:00',
  },
  {
    label: 'inputdatetime_31',
    inputValue: '1970-01-01 00:00',
    expectedValue: '1970-01-01 00:00',
    expectedInternalValue: '1970-01-01T00:00:00',
    expectedValueAfterSave: '1970-01-01T00:00:00',
  },
  {
    label: 'inputdatetime_35',
    inputValue: '2022-03-31 05:23',
    expectedValue: '2022-03-31 05:23',
    expectedInternalValue: '2022-03-31T05:23:00',
    expectedValueAfterSave: '2022-03-31T05:23:00',
  },
];

const dbTypeDateEvalTime: ElementsBasicInputTestData[] = [
  {
    label: 'inputdatetime_5',
    inputValue: '13:30',
    expectedValue: '13:30',
    expectedInternalValue: '1970-01-01T13:30:00',
    expectedValueAfterSave: '1970-01-01T13:30:00',
  },
];

const evalDateTime_DbTypeDateTime: ElementsBasicInputTestData[] = [
  {
    label: 'inputdatetime_4',
    inputValue: '2016-01-29 05:23',
    expectedValue: '2016-01-29 05:23',
    expectedInternalValue: '2016-01-29T05:23:00',
    expectedValueAfterSave: '2016-01-29T05:23:00',
  },
  {
    label: 'inputdatetime_4',
    inputValue: '2016-02-29 05:23',
    expectedValue: '2016-02-29 05:23',
    expectedInternalValue: '2016-02-29T05:23:00',
    expectedValueAfterSave: '2016-02-29T05:23:00',
  },
  {
    label: 'inputdatetime_6',
    inputValue: '13:30:00',
    expectedValue: '13:30:00',
    expectedInternalValue: '1970-01-01T13:30:00',
    expectedValueAfterSave: '1970-01-01T13:30:00',
  },
  {
    label: 'inputdatetime_36',
    inputValue: '1979-01-28 13:37:42',
    expectedValue: '1979-01-28 13:37:42',
    expectedInternalValue: '1979-01-28T13:37:42',
    expectedValueAfterSave: '1979-01-28T13:37:42',
  },
  {
    label: 'inputdatetime_37',
    inputValue: '1979-01-28 13:37:42',
    expectedValue: '1979-01-28 13:37:42',
    expectedInternalValue: '1979-01-28T13:37:42',
    expectedValueAfterSave: '1979-01-28T13:37:42',
  },
];

const formatTimeMidnight: ElementsBasicInputTestData[] = [
  // Field is not nullable, 00:00 is interpreted as empty after save.
  {
    label: 'inputdatetime_5',
    inputValue: '00:00',
    expectedValue: '00:00',
    expectedInternalValue: '1970-01-01T00:00:00',
    expectedInternalValueAfterSave: '',
    expectedValueAfterSave: '',
  },
  {
    label: 'inputdatetime_12',
    inputValue: '00:00',
    expectedValue: '00:00',
    expectedInternalValue: '1970-01-01T00:00:00',
    expectedValueAfterSave: '1970-01-01T00:00:00',
  },
  {
    label: 'inputdatetime_25',
    inputValue: '00:00',
    expectedValue: '00:00',
    expectedInternalValue: '1970-01-01T00:00:00',
    expectedValueAfterSave: '1970-01-01T00:00:00',
  },
  {
    label: 'inputdatetime_32',
    inputValue: '00:00',
    expectedValue: '00:00',
    expectedInternalValue: '1970-01-01T00:00:00',
    expectedValueAfterSave: '1970-01-01T00:00:00',
  },
];

const formatTimeAnteMeridiem: ElementsBasicInputTestData[] = [
  {
    label: 'inputdatetime_5',
    inputValue: '05:43',
    expectedValue: '05:43',
    expectedInternalValue: '1970-01-01T05:43:00',
    expectedValueAfterSave: '1970-01-01T05:43:00',
  },
  {
    label: 'inputdatetime_12',
    inputValue: '05:43',
    expectedValue: '05:43',
    expectedInternalValue: '1970-01-01T05:43:00',
    expectedValueAfterSave: '1970-01-01T05:43:00',
  },
  {
    label: 'inputdatetime_25',
    inputValue: '05:43',
    expectedValue: '05:43',
    expectedInternalValue: '1970-01-01T05:43:00',
    expectedValueAfterSave: '1970-01-01T05:43:00',
  },
  {
    label: 'inputdatetime_32',
    inputValue: '05:43',
    expectedValue: '05:43',
    expectedInternalValue: '1970-01-01T05:43:00',
    expectedValueAfterSave: '1970-01-01T05:43:00',
  },
];

const formatTimeEmpty: ElementsBasicInputTestData[] = [
  {
    label: 'inputdatetime_5',
    inputValue: '',
    expectedValue: '',
    expectedInternalValue: '',
    expectedValueAfterSave: '',
  },
  {
    label: 'inputdatetime_12',
    inputValue: '',
    expectedValue: '',
    expectedInternalValue: '',
    expectedValueAfterSave: '',
  },
  {
    label: 'inputdatetime_25',
    inputValue: '',
    expectedValue: '',
    expectedInternalValue: '',
    expectedValueAfterSave: '',
  },
  {
    label: 'inputdatetime_32',
    inputValue: '',
    expectedValue: '',
    expectedInternalValue: '',
    expectedValueAfterSave: '',
  },
];

const formatTimesecMidnight: ElementsBasicInputTestData[] = [
  // Field is not nullable, 00:00 is interpreted as empty after save.
  {
    label: 'inputdatetime_6',
    inputValue: '00:00:00',
    expectedValue: '00:00:00',
    expectedInternalValue: '1970-01-01T00:00:00',
    expectedInternalValueAfterSave: '',
    expectedValueAfterSave: '',
  },
  {
    label: 'inputdatetime_13',
    inputValue: '00:00:00',
    expectedValue: '00:00:00',
    expectedInternalValue: '1970-01-01T00:00:00',
    expectedValueAfterSave: '1970-01-01T00:00:00',
  },
  {
    label: 'inputdatetime_26',
    inputValue: '00:00:00',
    expectedValue: '00:00:00',
    expectedInternalValue: '1970-01-01T00:00:00',
    expectedValueAfterSave: '1970-01-01T00:00:00',
  },
  {
    label: 'inputdatetime_33',
    inputValue: '00:00:00',
    expectedValue: '00:00:00',
    expectedInternalValue: '1970-01-01T00:00:00',
    expectedValueAfterSave: '1970-01-01T00:00:00',
  },
  {
    label: 'inputdatetime_36',
    inputValue: '1979-01-28 00:00:00',
    expectedValue: '1979-01-28 00:00:00',
    expectedInternalValue: '1979-01-28T00:00:00',
    expectedValueAfterSave: '1979-01-28T00:00:00',
  },
  {
    label: 'inputdatetime_37',
    inputValue: '1979-01-28 00:00:00',
    expectedValue: '1979-01-28 00:00:00',
    expectedInternalValue: '1979-01-28T00:00:00',
    expectedValueAfterSave: '1979-01-28T00:00:00',
  },
];

const formatTimesecAnteMeridiem: ElementsBasicInputTestData[] = [
  {
    label: 'inputdatetime_6',
    inputValue: '05:43:21',
    expectedValue: '05:43:21',
    expectedInternalValue: '1970-01-01T05:43:21',
    expectedValueAfterSave: '1970-01-01T05:43:21',
  },
  {
    label: 'inputdatetime_13',
    inputValue: '05:43:21',
    expectedValue: '05:43:21',
    expectedInternalValue: '1970-01-01T05:43:21',
    expectedValueAfterSave: '1970-01-01T05:43:21',
  },
  {
    label: 'inputdatetime_26',
    inputValue: '05:43:21',
    expectedValue: '05:43:21',
    expectedInternalValue: '1970-01-01T05:43:21',
    expectedValueAfterSave: '1970-01-01T05:43:21',
  },
  {
    label: 'inputdatetime_33',
    inputValue: '05:43:21',
    expectedValue: '05:43:21',
    expectedInternalValue: '1970-01-01T05:43:21',
    expectedValueAfterSave: '1970-01-01T05:43:21',
  },
  {
    label: 'inputdatetime_36',
    inputValue: '1979-01-28 05:43:21',
    expectedValue: '1979-01-28 05:43:21',
    expectedInternalValue: '1979-01-28T05:43:21',
    expectedValueAfterSave: '1979-01-28T05:43:21',
  },
  {
    label: 'inputdatetime_37',
    inputValue: '1979-01-28 05:43:21',
    expectedValue: '1979-01-28 05:43:21',
    expectedInternalValue: '1979-01-28T05:43:21',
    expectedValueAfterSave: '1979-01-28T05:43:21',
  },
];

const formatTimesecEmpty: ElementsBasicInputTestData[] = [
  {
    label: 'inputdatetime_6',
    inputValue: '',
    expectedValue: '',
    expectedInternalValue: '',
    expectedValueAfterSave: '',
  },
  {
    label: 'inputdatetime_13',
    inputValue: '',
    expectedValue: '',
    expectedInternalValue: '',
    expectedValueAfterSave: '',
  },
  {
    label: 'inputdatetime_26',
    inputValue: '',
    expectedValue: '',
    expectedInternalValue: '',
    expectedValueAfterSave: '',
  },
  {
    label: 'inputdatetime_33',
    inputValue: '',
    expectedValue: '',
    expectedInternalValue: '',
    expectedValueAfterSave: '',
  },
  {
    label: 'inputdatetime_38',
    inputValue: '',
    expectedValue: '',
    expectedInternalValue: '',
    expectedValueAfterSave: '',
  },
  {
    label: 'inputdatetime_39',
    inputValue: '',
    expectedValue: '',
    expectedInternalValue: '',
    expectedValueAfterSave: '',
  },
];

const groups: { name: string, cases: ElementsBasicInputTestData[] }[] = [
  { name: 'dbType=date eval=date', cases: dbTypeDateEvalDate },
  { name: 'dbType=date eval=datetime', cases: dbTypeDateEvalDatetime },
  { name: 'dbType=date eval=time', cases: dbTypeDateEvalTime },
  { name: 'eval=datetime dbType=datetime', cases: evalDateTime_DbTypeDateTime },
  { name: 'format=time midnight', cases: formatTimeMidnight },
  { name: 'format=time ante meridiem', cases: formatTimeAnteMeridiem },
  { name: 'format=time empty', cases: formatTimeEmpty },
  { name: 'format=timesec midnight', cases: formatTimesecMidnight },
  { name: 'format=timesec ante meridiem', cases: formatTimesecAnteMeridiem },
  { name: 'format=timesec empty', cases: formatTimesecEmpty },
];

for (const group of groups) {
  for (const [index, data] of group.cases.entries()) {
    test(`elements_basic ${group.name} [${index}] ${data.label}: ${data.inputValue || '<empty>'}`, async ({ backend }) => {
      await backend.formEngine.runInputFieldTest(data, { datepicker: true });
    });
  }
}
