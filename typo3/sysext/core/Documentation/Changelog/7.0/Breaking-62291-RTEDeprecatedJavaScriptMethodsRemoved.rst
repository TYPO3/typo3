
.. include:: /Includes.rst.txt

============================================================
Breaking: #62291 - RTE Deprecated JavaScript methods removed
============================================================

See :issue:`62291`

Description
===========

The following RTE JavaScript methods, deprecated since TYPO3 4.7, have been removed:

HTMLArea.Editor.forceRedraw: use HTMLArea.Framework.doLayout instead

HTMLArea.Editor.convertNode: use HTMLArea.DOM.convertNode instead
HTMLArea.Editor.getBlockAncestors: use HTMLArea.DOM.getBlockAncestors instead
HTMLArea.getInnerText: use HTMLArea.DOM.getInnerText instead
HTMLArea.hasAllowedAttributes: use HTMLArea.DOM.hasAllowedAttributes instead
HTMLArea.isBlockElement: use HTMLArea.DOM.isBlockElement instead
HTMLArea.needsClosingTag: use HTMLArea.DOM.needsClosingTag instead
HTMLArea.Editor.rangeIntersectsNode: use HTMLArea.DOM.rangeIntersectsNode instead
HTMLArea.removeFromParent: use HTMLArea.DOM.removeFromParent instead

HTMLArea.Editor.cleanAppleStyleSpans: use HTMLArea.DOM.Node.cleanAppleStyleSpans instead
HTMLArea.Editor.removeMarkup: use HTMLArea.DOM.Node.removeMarkup instead
HTMLArea.Editor.wrapWithInlineElement: use HTMLArea.DOM.Node.wrapWithInlineElement instead

HTMLArea.Editor.addRangeToSelection: use HTMLArea.DOM.Selection.addRange instead
HTMLArea.Editor._createRange: use HTMLArea.DOM.Selection.createRange instead
HTMLArea.Editor.emptySelection: use HTMLArea.DOM.Selection.empty instead
HTMLArea.Editor.endPointsInSameBlock: use HTMLArea.DOM.Selection.endPointsInSameBlock instead
HTMLArea.Editor.execCommand: use HTMLArea.DOM.Selection.execCommand instead
HTMLArea.Editor._getSelection: use HTMLArea.DOM.Selection.get instead
HTMLArea.Editor.getAllAncestors: use HTMLArea.DOM.Selection.getAllAncestors instead
HTMLArea.Editor.getSelectedElement: use HTMLArea.DOM.Selection.getElement instead
HTMLArea.Editor.getEndBlocks: use HTMLArea.DOM.Selection.getEndBlocks instead
HTMLArea.Editor._getFirstAncestor: use HTMLArea.DOM.Selection.getFirstAncestorOfType instead
HTMLArea.Editor.getFullySelectedNode: use HTMLArea.DOM.Selection.getFullySelectedNode instead
HTMLArea.Editor.getSelectedHTML: use HTMLArea.DOM.Selection.getHtml instead
HTMLArea.Editor.getSelectedHTMLContents: use HTMLArea.DOM.Selection.getHtml instead
HTMLArea.Editor.getParentElement: use HTMLArea.DOM.Selection.getParentElement instead
HTMLArea.Editor.getSelectionRanges: use HTMLArea.DOM.Selection.getRanges instead
HTMLArea.Editor.getSelectionType: use HTMLArea.DOM.Selection.getType instead
HTMLArea.Editor.insertHTML: use HTMLArea.DOM.Selection.insertHtml instead
HTMLArea.Editor.insertNodeAtSelection: use HTMLArea.DOM.Selection.insertNode instead
HTMLArea.Editor._selectionEmpty: use HTMLArea.DOM.Selection.isEmpty instead
HTMLArea.Editor.hasSelectedText: use !HTMLArea.DOM.Selection.isEmpty instead
HTMLArea.Editor.selectNode: use HTMLArea.DOM.Selection.selectNode instead
HTMLArea.Editor.selectNodeContents: use HTMLArea.DOM.Selection.selectNodeContents instead
HTMLArea.Editor.selectRange: use HTMLArea.DOM.Selection.selectRange instead
HTMLArea.Editor.setSelectionRanges: use HTMLArea.DOM.Selection.setRanges instead
HTMLArea.Editor.surroundHTML: use HTMLArea.DOM.Selection.surroundHtml instead

HTMLArea.Editor.getBookmark: use HTMLArea.DOM.BookMark.get instead
HTMLArea.Editor.getBookmarkNode: use HTMLArea.DOM.BookMark.getEndPoint instead
HTMLArea.Editor.moveToBookmark: use HTMLArea.DOM.BookMark.moveTo instead

HTMLArea.htmlDecode: use HTMLArea.util.htmlDecode instead
HTMLArea.htmlEncode: use HTMLArea.util.htmlEncode instead

Impact
======

3rd party extensions adding plugins to the RTE and using the removed methods will fail.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension of the RTE refers to the removed methods.


Migration
=========

The affected 3rd party extensions must be modified to use the replacement methods.


.. index:: JavaScript, RTE, Backend
