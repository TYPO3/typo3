
.. include:: /Includes.rst.txt

===================================================
Feature: #65250 - TypoScript condition add GPmerged
===================================================

See :issue:`65250`


Description
===========

If one uses TypoScript condition with GP then the check is with GeneralUtility::_GP()
which means that if I have GET variables beginning with an extbase plugin-namespace
and POST variables with the same plugin-namespace, e.g.
GET: tx_demo_demo[action]=detail
POST: tx_demo_demo[name]=Foo
then GeneralUtility::_GP('tx_demo_demo'), as intended, will only return the
array of the POST variables for that namespace. However, that results in the issue that
if you check for the GET variable the check will fail.

So, instead the check should use GeneralUtility::_GPmerged()

.. code-block:: typoscript

	[globalVar = GPmerged:tx_demo|foo = 1]
	page.90 = TEXT
	page.90.value = DEMO
	[global]


.. index:: TypoScript, Frontend
