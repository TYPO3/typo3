
.. include:: ../../Includes.txt

.. role::   typoscript(code)
.. role::   ts(typoscript)

   :class:  typoscript

============================================================
Breaking: #42543 - Default TypoScript Removed
============================================================

See :issue:`42543`

Description
===========

The default TypoScript :code:`styles.insertContent` was removed without substitution.


Impact
======

Frontend output may change.


Affected installations
======================

A TYPO3 instance is affected if its TypoScript operates with :code:`styles.insertContent`. This is pretty unlikely since the styles segment was hidden in the TypoScript Object Browser.


Migration
=========

Either remove usage of :code:`styles.insertContent` or add a snippet at an early point in TypoScript for backwards compatibility.

.. code-block:: typoscript

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
