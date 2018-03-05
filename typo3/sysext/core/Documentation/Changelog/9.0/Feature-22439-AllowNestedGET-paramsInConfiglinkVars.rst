.. include:: ../../Includes.txt

============================================================
Feature: #22439 - Allow nested GET-params in config.linkVars
============================================================

See :issue:`22439`

Description
===========

TypoScript setting :ts:`config.linkVars` configures which parameters should be passed on with links in TYPO3.
It is now possible to specify nested GET parameters there.

Example:

.. code-block:: typoscript

   config.linkVars = L(0-2),tracking|green(0-5)

With the above configuration the following example GET parameters will be kept:

&L=1&tracking[green]=3

But a get parameter like tracking[blue] will not be kept.

The value constraint in round brackets works in the same way as for not nested GET parameters.

.. index:: Frontend, TypoScript
