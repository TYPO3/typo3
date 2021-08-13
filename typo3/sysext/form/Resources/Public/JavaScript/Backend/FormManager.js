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
 * Module: TYPO3/CMS/Form/Backend/FormManager
 */
define(['jquery'], function($) {
  'use strict';

  /**
   * Return a static method named "getInstance".
   * Use this method to create the formmanager app.
   */
  return (function() {

    /**
     * @private
     *
     * Hold the instance (Singleton Pattern)
     */
    var _formManagerInstance = null;

    /**
     * @public
     *
     * @param object _configuration
     * @param object _viewModel
     * @return object
     */
    function FormManager(_configuration, _viewModel) {

      /**
       * @private
       *
       * @var bool
       */
      var _isRunning = false;

      /**
       * @public
       *
       * @param mixed test
       * @param string message
       * @param int messageCode
       * @return void
       */
      function assert(test, message, messageCode) {
        if ('function' === $.type(test)) {
          test = (test() !== false);
        }
        if (!test) {
          message = message || "Assertion failed";
          if (messageCode) {
            message = message + ' (' + messageCode + ')';
          }
          if ('undefined' !== typeof Error) {
            throw new Error(message);
          }
          throw message;
        }
      };

      /**
       * @public
       *
       * @return object
       */
      function getPrototypes() {
        var prototypes = [];

        if ('array' === $.type(_configuration['selectablePrototypesConfiguration'])) {
          for (var i = 0, len = _configuration['selectablePrototypesConfiguration'].length; i < len; ++i) {
            prototypes.push({
              label: _configuration['selectablePrototypesConfiguration'][i]['label'],
              value: _configuration['selectablePrototypesConfiguration'][i]['identifier']
            });
          }
        }
        return prototypes;
      };

      /**
       * @public
       *
       * @param string prototypeName
       * @return object
       */
      function getTemplatesForPrototype(prototypeName) {
        var templates = [];
        assert('string' === $.type(prototypeName), 'Invalid parameter "prototypeName"', 1475945286);
        if ('array' === $.type(_configuration['selectablePrototypesConfiguration'])) {
          for (var i = 0, len1 = _configuration['selectablePrototypesConfiguration'].length; i < len1; ++i) {
            if (_configuration['selectablePrototypesConfiguration'][i]['identifier'] !== prototypeName) {
              continue;
            }
            if ('array' === $.type(_configuration['selectablePrototypesConfiguration'][i]['newFormTemplates'])) {
              for (var j = 0, len2 = _configuration['selectablePrototypesConfiguration'][i]['newFormTemplates'].length; j < len2; ++j) {
                templates.push({
                  label: _configuration['selectablePrototypesConfiguration'][i]['newFormTemplates'][j]['label'],
                  value: _configuration['selectablePrototypesConfiguration'][i]['newFormTemplates'][j]['templatePath']
                });
              }
            }
          }
        }

        return templates;
      };

      /**
       * @public
       *
       * @param string prototypeName
       * @return object
       */
      function getAccessibleFormStorageFolders() {
        var folders = [];

        if ('array' === $.type(_configuration['accessibleFormStorageFolders'])) {
          for (var i = 0, len1 = _configuration['accessibleFormStorageFolders'].length; i < len1; ++i) {
            folders.push({
              label: _configuration['accessibleFormStorageFolders'][i]['label'],
              value: _configuration['accessibleFormStorageFolders'][i]['value']
            });
          }
        }
        return folders;
      };

      /**
       * @public
       *
       * @param string prototypeName
       * @return object
       * @throws 1477506508
       */
      function getAjaxEndpoint(endpointName) {
        var templates = [];
        assert(typeof _configuration['endpoints'][endpointName] !== 'undefined', 'Endpoint ' + endpointName + ' does not exist', 1477506508);

        return _configuration['endpoints'][endpointName];
      };

      /**
       * @private
       *
       * @return void
       * @throws 1475942906
       */
      function _viewSetup() {
        assert('function' === $.type(_viewModel.bootstrap), 'The view model does not implement the method "bootstrap"', 1475942906);
        _viewModel.bootstrap(_formManagerInstance);
      };

      /**
       * @private
       *
       * @return void
       * @throws 1477506504
       */
      function _bootstrap() {
        _configuration = _configuration || {};
        assert('object' === $.type(_configuration['endpoints']), 'Invalid parameter "endpoints"', 1477506504);
        _viewSetup();
      };

      /**
       * @public
       *
       * @return TYPO3/CMS/Form/Backend/FormManager
       * @throws 1475942618
       */
      function run() {
        if (_isRunning) {
          throw 'You can not run the app twice (1475942618)';
        }

        _bootstrap();
        _isRunning = true;
        return this;
      };

      /**
       * Publish the public methods.
       * Implements the "Revealing Module Pattern".
       */
      return {
        getPrototypes: getPrototypes,
        getTemplatesForPrototype: getTemplatesForPrototype,
        getAccessibleFormStorageFolders: getAccessibleFormStorageFolders,
        getAjaxEndpoint: getAjaxEndpoint,

        assert: assert,
        run: run
      };
    };

    /**
     * Emulation of static methods
     */
    return {
      /**
       * @public
       * @static
       *
       * Implement the "Singleton Pattern".
       *
       * Return a singleton instance of a
       * "FormManager" object.
       *
       * @param object configuration
       * @param object viewModel
       * @return object
       */
      getInstance: function(configuration, viewModel) {
        if (_formManagerInstance === null) {
          _formManagerInstance = new FormManager(configuration, viewModel);
        }
        return _formManagerInstance;
      }
    };
  })();
});
