
.. include:: ../../Includes.txt

=====================================================================================
Breaking: #62804 - RTE JavaScript method HTMLArea.Editor::getNodeByPosition was moved
=====================================================================================

See :issue:`62804`

Description
===========

RTE JavaScript method :code:`getNodeByPosition()` was moved from HTMLArea.Editor to HTMLArea.DOM.Node where it belongs.


Impact
======

3rd party extensions referring to :code:`HTMLArea.Editor::getNodeByPosition()` will fail.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension refers to the method :code:`HTMLArea.Editor::getNodeByPosition()`.


Migration
=========

The affected 3rd party extensions must be modified to use method :code:`HTMLArea.DOM.Node::getNodeByPosition()`
instead.


.. index:: RTE, JavaScript, Backend
