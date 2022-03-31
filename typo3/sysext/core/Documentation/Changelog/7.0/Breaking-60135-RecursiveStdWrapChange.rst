
.. include:: /Includes.rst.txt

============================================================
Breaking: #60135 - Recursive stdWrap is now only called once
============================================================

See :issue:`60135`

Description
===========

If recursive stdWrap configuration was used, the stdWrap method was called twice, although the content
was only rendered once. This performance overhead is now removed.


Impact
======

If a recursive stdWrap configuration is used, which sets or acts on a global state like registers,
the resulting rendering can now be different because the global state is now modified only once.


Affected installations
======================

A TYPO3 instance is affected if there is TypoScript code like:

::

  page.1 = TEXT
  page.1 {
    value = Counter:
    append = TEXT
    append.data = register:Counter
    stdWrap.append = LOAD_REGISTER
    stdWrap.append {
      Counter.cObject = TEXT
      Counter.cObject.data = register:Counter
      Counter.cObject.wrap = |+1
      Counter.prioriCalc = 1
    }
  }

..

This now correctly outputs `Counter:1` instead of `Counter:2`

Migration
=========

The usage of recursive stdWrap TypoScript configuration needs to be checked and probably adapted to fit the fixed behavior.


.. index:: TypoScript, Frontend
