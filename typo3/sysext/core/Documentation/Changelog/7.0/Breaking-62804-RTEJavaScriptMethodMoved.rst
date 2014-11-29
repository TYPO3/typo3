=====================================================================================
Breaking: #62804 - RTE JavaScript method HTMLArea.Editor::getNodeByPosition was moved
=====================================================================================

Description
===========

RTE JavaScript method :js:`getNodeByPosition()` was moved from HTMLArea.Editor to HTMLArea.DOM.Node where it belongs.


Impact
======

3rd party extensions referring to :js:`HTMLArea.Editor::getNodeByPosition()` will fail.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension refers to the method :js:`HTMLArea.Editor::getNodeByPosition()`.


Migration
=========

The affected 3rd party extensions must be modified to use method :js:`HTMLArea.DOM.Node::getNodeByPosition()`
instead.