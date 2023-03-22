if (typeof globalThis.TYPO3 === 'undefined') {
  globalThis.TYPO3 = globalThis.TYPO3 || {};
  globalThis.TYPO3.settings = {
    'FormEngine': {
      'formName': 'Test'
    },
    'DateTimePicker': {
      'DateFormat': 'd.m.Y'
    },
    'ajaxUrls': {
    }
  };
  globalThis.TYPO3.lang = {};
}

top.TYPO3 = globalThis.TYPO3;
globalThis.TBE_EDITOR = {};
