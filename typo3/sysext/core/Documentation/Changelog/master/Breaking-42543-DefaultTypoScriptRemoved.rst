.. role::   typoscript(code)
.. role::   ts(typoscript)

   :class:  typoscript

============================================================
Breaking: #42543 - Default TypoScript Removed
============================================================

Description
===========

The default TypoScript :ts:`styles.insertContent` was removed without substitution.


Impact
======

Frontend output may change.


Affected installations
======================

A TYPO3 instance is affected if own TypoScript operates with :ts:`styles.insertContent`. This is pretty unlikely since the styles segment was hidden in the TypoScript Object Browser.


Migration
=========

Either remove usage of :ts:`styles.insertContent` or add a snippet at an early point in TypoScript for backwards compatibility.

::

  styles.insertContent = CONTENT
  styles.insertContent {
    table = tt_content
    select {
      orderBy = sorting
      where = colPos=0
      languageField = sys_language_uid
    }
  }

..