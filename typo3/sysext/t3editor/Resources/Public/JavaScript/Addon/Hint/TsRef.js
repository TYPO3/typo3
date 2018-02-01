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
 * Module: TYPO3/CMS/T3editor/CodeCompletion/TsRef
 * Contains the TsCodeCompletion class
 */
define(['jquery'], function($) {
  /**
   *
   * @type {{typeId: null, properties: null, typeTree: Array, doc: null}}
   * @exports TYPO3/CMS/T3editor/CodeCompletion/TsRef
   */
  var TsRef = {
    typeId: null,
    properties: null,
    typeTree: [],
    doc: null
  };

  /**
   * Prototypes a TS reference type object
   *
   * @param {String} typeId
   */
  TsRef.TsRefType = function(typeId) {
    this.typeId = typeId;
    this.properties = [];
  };

  /**
   * Prototypes a TS reference property object
   *
   * @param {String} parentType
   * @param {String} name
   * @param {String} value
   * @constructor
   */
  TsRef.TsRefProperty = function(parentType, name, value) {
    this.parentType = parentType;
    this.name = name;
    this.value = value;
  };

  /**
   * Load available TypoScript reference
   */
  TsRef.loadTsrefAsync = function() {
    $.ajax({
      url: TYPO3.settings.ajaxUrls['t3editor_tsref'],
      success: function(response) {
        TsRef.doc = response;
        TsRef.buildTree();
      }
    });
  };

  /**
   * Build the TypoScript reference tree
   */
  TsRef.buildTree = function() {
    for (var typeId in TsRef.doc) {
      var arr = TsRef.doc[typeId];
      TsRef.typeTree[typeId] = new TsRef.TsRefType(typeId);

      if (typeof arr['extends'] !== 'undefined') {
        TsRef.typeTree[typeId]['extends'] = arr['extends'];
      }
      for (var propName in arr.properties) {
        var propType = arr.properties[propName].type;
        TsRef.typeTree[typeId].properties[propName] = new TsRef.TsRefProperty(typeId, propName, propType);
      }
    }
    for (var typeId in TsRef.typeTree) {
      if (typeof TsRef.typeTree[typeId]['extends'] !== 'undefined') {
        TsRef.addPropertiesToType(TsRef.typeTree[typeId], TsRef.typeTree[typeId]['extends'], 100);
      }
    }
  };

  /**
   * Adds properties to TypoScript types
   *
   * @param {String} addToType
   * @param {String} addFromTypeNames
   * @param {Number} maxRecDepth
   */
  TsRef.addPropertiesToType = function(addToType, addFromTypeNames, maxRecDepth) {
    if (maxRecDepth < 0) {
      throw "Maximum recursion depth exceeded while trying to resolve the extends in the TSREF!";
      return;
    }
    var exts = addFromTypeNames.split(','),
      i;
    for (i = 0; i < exts.length; i++) {
      // "Type 'array' which is used to extend 'undefined', was not found in the TSREF!"
      if (typeof TsRef.typeTree[exts[i]] !== 'undefined') {
        if (typeof TsRef.typeTree[exts[i]]['extends'] !== 'undefined') {
          TsRef.addPropertiesToType(TsRef.typeTree[exts[i]], TsRef.typeTree[exts[i]]['extends'], maxRecDepth - 1);
        }
        var properties = TsRef.typeTree[exts[i]].properties;
        for (var propName in properties) {
          // only add this property if it was not already added by a supertype (subtypes override supertypes)
          if (typeof addToType.properties[propName] === 'undefined') {
            addToType.properties[propName] = properties[propName];
          }
        }
      }
    }
  };

  /**
   * Get properties from given TypoScript type id
   *
   * @param {String} tId
   * @return {Array}
   */
  TsRef.getPropertiesFromTypeId = function(tId) {
    if (typeof TsRef.typeTree[tId] !== 'undefined') {
      // clone is needed to assure that nothing of the tsref is overwritten by user setup
      TsRef.typeTree[tId].properties.clone = function() {
        var result = [];
        for (key in this) {
          result[key] = new TsRef.TsRefProperty(this[key].parentType, this[key].name, this[key].value);
        }
        return result;
      }
      return TsRef.typeTree[tId].properties;
    }
    return [];
  };

  /**
   * Check if a property of a type exists
   *
   * @param {String} typeId
   * @param {String} propertyName
   * @return {Boolean}
   */
  TsRef.typeHasProperty = function(typeId, propertyName) {
    return typeof TsRef.typeTree[typeId] !== 'undefined'
      && typeof TsRef.typeTree[typeId].properties[propertyName] !== 'undefined';
  };

  /**
   * Get the type
   *
   * @param {String} typeId
   * @return {Object}
   */
  TsRef.getType = function(typeId) {
    return TsRef.typeTree[typeId];
  };

  /**
   * Check if type exists in the type tree
   *
   * @param {String} typeId
   * @return {Boolean}
   */
  TsRef.isType = function(typeId) {
    return typeof TsRef.typeTree[typeId] !== 'undefined';
  };

  return TsRef;
});
