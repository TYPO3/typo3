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
/**
 * This handler is used as client-side counterpart of `\TYPO3\CMS\Core\Page\JavaScriptRenderer`.
 */
(function() {
  // @todo Handle document.currentScript.async
  if (!document.currentScript) {
    return false;
  }
  const FLAG_LOAD_REQUIRE_JS = 1;
  const deniedProperties = ['__proto__', 'prototype', 'constructor'];
  const supportedItemTypes = ['assign', 'invoke', 'instance'];
  const scriptElement = document.currentScript;
  const handlers = {
    /**
     * @param {string} type sub-handler type (processItems, loadRequireJs, globalAssignment, javaScriptModuleInstruction)
     */
    processType: (type) => {
      // extracts JSON payload from `/* [JSON] */` content
      invokeHandler(type, scriptElement.textContent.replace(/^\s*\/\*\s*|\s*\*\/\s*/g, ''));
    },
    /**
     * Processes multiple items and delegates to sub-handlers (processItems, loadRequireJs, globalAssignment, javaScriptModuleInstruction)
     * @param {string} data JSON data
     */
    processItems: (data) => {
      const json = JSON.parse(data);
      if (!isArrayInstance(json)) {
        return;
      }
      json.forEach((item) => invokeHandler(item.type, item.payload, true));
    },
    /**
     * Initializes require.js configuration - require.js sources must be loaded already.
     * @param {string} data JSON data
     * @param {boolean} isParsed whether data has been parsed already
     */
    loadRequireJS: (data, isParsed) => {
      const payload = isParsed ? data : JSON.parse(data);
      if (!isObjectInstance(payload)) {
        return;
      }
      require.config(payload.config);
    },
    /**
     * Assigns (filtered) variables to `window` object globally.
     * @param {string} data JSON data
     * @param {boolean} isParsed whether data has been parsed already
     */
    globalAssignment: (data, isParsed) => {
      const payload = isParsed ? data : JSON.parse(data);
      if (!isObjectInstance(payload)) {
        return;
      }
      mergeRecursive(window, payload);
    },
    /**
     * Loads and invokes a requires.js module (AMD).
     * @param {string} data JSON data
     * @param {boolean} isParsed whether data has been parsed already
     */
    javaScriptModuleInstruction: (data, isParsed) => {
      const payload = isParsed ? data : JSON.parse(data);
      if ((payload.flags & FLAG_LOAD_REQUIRE_JS) === FLAG_LOAD_REQUIRE_JS) {
        loadRequireJsModule(payload);
      }
    }
  };

  function loadRequireJsModule(json) {
    // `name` is required
    if (!json.name) {
      return;
    }
    if (!json.items) {
      require([json.name]);
      return;
    }
    const exportName = json.exportName;
    const resolveSubjectRef = (__esModule) => {
      return typeof exportName === 'string' ? __esModule[exportName] : __esModule;
    }
    const items = json.items
      .filter((item) => supportedItemTypes.includes(item.type))
      .map((item) => {
        if (item.type === 'assign') {
          return (__esModule) => {
            const subjectRef = resolveSubjectRef(__esModule);
            mergeRecursive(subjectRef, item.assignments);
          };
        } else if (item.type === 'invoke') {
          return (__esModule) => {
            const subjectRef = resolveSubjectRef(__esModule);
            subjectRef[item.method].apply(subjectRef, item.args);
          };
        } else if (item.type === 'instance') {
          return (__esModule) => {
            // this `null` is `thisArg` scope of `Function.bind`,
            // which will be reset when invoking `new`
            const args = [null].concat(item.args);
            const subjectRef = resolveSubjectRef(__esModule);
            new (subjectRef.bind.apply(subjectRef, args));
          }
        }
      });
    require(
      [json.name],
      (subjectRef) => items.forEach((item) => item.call(null, subjectRef))
    );
  }

  function isObjectInstance(item) {
    return item instanceof Object && !(item instanceof Array);
  }
  function isArrayInstance(item) {
    return item instanceof Array;
  }
  function mergeRecursive(target, source) {
    Object.keys(source).forEach((property) => {
      if (deniedProperties.indexOf(property) !== -1) {
        throw new Error('Property ' + property + ' is not allowed');
      }
      if (!isObjectInstance(source[property]) || typeof target[property] === 'undefined') {
        Object.assign(target, {[property]:source[property]});
      } else {
        mergeRecursive(target[property], source[property]);
      }
    });
  }

  function invokeHandler(name, data, isParsed) {
    if (typeof handlers[name] === 'undefined') {
      return;
    }
    handlers[name].call(null, data, Boolean(isParsed));
  }

  // start processing dataset declarations
  Object.keys(scriptElement.dataset)
    .forEach((name) => {
      try {
        invokeHandler(name, scriptElement.dataset[name]);
      } catch (e) {
        console.error(e);
      }
    });
})();
