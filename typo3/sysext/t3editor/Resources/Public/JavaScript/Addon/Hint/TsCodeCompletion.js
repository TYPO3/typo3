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
 * Module: TYPO3/CMS/T3editor/Addon/Hint/TsCodeCompletion
 * Contains the TsCodeCompletion class
 */
define([
  'jquery',
  'TYPO3/CMS/T3editor/Addon/Hint/TsRef',
  'TYPO3/CMS/T3editor/Addon/Hint/TsParser',
  'TYPO3/CMS/T3editor/Addon/Hint/CompletionResult'
], function($, TsRef, TsParser, CompletionResult) {
  /**
   *
   * @type {{tsRef: *, proposals: null, compResult: null, currentCursorPosition: null, extTsObjTree: {}, codemirror: null, parser: null, plugins: string[]}}
   * @exports TYPO3/CMS/T3editor/CodeCompletion/TsCodeCompletion
   */
  var TsCodeCompletion = {
    tsRef: TsRef,
    proposals: null,
    compResult: null,
    currentCursorPosition: null,
    extTsObjTree: {},
    codemirror: null,
    parser: null
  };

  /**
   * All external templates along the rootline have to be loaded,
   * this function retrieves the JSON code by committing a AJAX request
   *
   * @param {number} id
   */
  TsCodeCompletion.loadExtTemplatesAsync = function(id) {
    // Ensure id is an integer
    id *= 1;
    if (Number.isNaN(id) || id === 0) {
      return null;
    }
    $.ajax({
      url: TYPO3.settings.ajaxUrls['t3editor_codecompletion_loadtemplates'],
      data: {
        pageId: id
      },
      dataType: 'json',
      success: function(response) {
        TsCodeCompletion.extTsObjTree.c = response;
        TsCodeCompletion.resolveExtReferencesRec(TsCodeCompletion.extTsObjTree.c);
      }
    });
  };

  /**
   * Since the references are not resolved server side we have to do it client-side
   * Benefit: less loading time due to less data which has to be transmitted
   *
   * @param {Array} childNodes
   */
  TsCodeCompletion.resolveExtReferencesRec = function(childNodes) {
    for (var key in childNodes) {
      var childNode;
      // if the childnode has a value and there is a part of a reference operator ('<')
      // and it does not look like a html tag ('>')
      if (childNodes[key].v && childNodes[key].v[0] === '<' && childNodes[key].v.indexOf('>') === -1) {
        var path = $.trim(childNodes[key].v.replace(/</, ''));
        // if there are still whitespaces it's no path
        if (path.indexOf(' ') === -1) {
          childNode = TsCodeCompletion.getExtChildNode(path);
          // if the node was found - reference it
          if (childNode !== null) {
            childNodes[key] = childNode;
          }
        }
      }
      // if there was no reference-resolving then we go deeper into the tree
      if (!childNode && childNodes[key].c) {
        TsCodeCompletion.resolveExtReferencesRec(childNodes[key].c);
      }
    }
  };

  /**
   * Get the child node of given path
   *
   * @param {String} path
   * @returns {Object}
   */
  TsCodeCompletion.getExtChildNode = function(path) {
    var extTree = TsCodeCompletion.extTsObjTree,
      path = path.split('.'),
      pathSeg;

    for (var i = 0; i < path.length; i++) {
      pathSeg = path[i];
      if (typeof extTree.c === 'undefined' || typeof extTree.c[pathSeg] === 'undefined') {
        return null;
      }
      extTree = extTree.c[pathSeg];
    }
    return extTree;
  };

  /**
   *
   * @returns {String}
   */
  TsCodeCompletion.getFilter = function() {
    var currentLine = TsCodeCompletion.codemirror.getTokenAt(TsCodeCompletion.currentCursorPosition).string;
    if (currentLine) {
      var filter = currentLine.replace('.', '');
      return filter.replace(/\s/g, '');
    }
    return '';
  };

  /**
   * Refreshes the code completion list based on the cursor's position
   */
  TsCodeCompletion.refreshCodeCompletion = function(codemirror) {
    TsCodeCompletion.codemirror = codemirror;

    // retrieves the node right to the cursor
    TsCodeCompletion.currentCursorPosition = TsCodeCompletion.codemirror.doc.getCursor();

    // the cursornode has to be stored cause inserted breaks have to be deleted after pressing enter if the codecompletion is active
    var filter = TsCodeCompletion.getFilter();

    if (TsCodeCompletion.compResult === null || TsCodeCompletion.codemirror.getTokenAt(TsCodeCompletion.currentCursorPosition).string === '.') {
      // TODO: implement cases: operatorCompletion reference/copy path completion (formerly found in getCompletionResults())
      var currentTsTreeNode = TsCodeCompletion.parser.buildTsObjTree(TsCodeCompletion.codemirror);
      TsCodeCompletion.compResult = CompletionResult.init({
        tsRef: TsRef,
        tsTreeNode: currentTsTreeNode
      });
    }

    TsCodeCompletion.proposals = TsCodeCompletion.compResult.getFilteredProposals(filter);

    var proposals = [];
    for (var i = 0; i < TsCodeCompletion.proposals.length; i++) {
      proposals[i] = TsCodeCompletion.proposals[i].word;
    }

    return proposals;
  };

  /**
   * Resets the completion list
   */
  TsCodeCompletion.resetCompList = function() {
    TsCodeCompletion.compResult = null;
  };

  $(function() {
    TsCodeCompletion.parser = TsParser.init(TsCodeCompletion.tsRef, TsCodeCompletion.extTsObjTree);
    TsCodeCompletion.tsRef.loadTsrefAsync();
    TsCodeCompletion.loadExtTemplatesAsync($('input[name="effectivePid"]').first().val());
  });

  return TsCodeCompletion;
});
