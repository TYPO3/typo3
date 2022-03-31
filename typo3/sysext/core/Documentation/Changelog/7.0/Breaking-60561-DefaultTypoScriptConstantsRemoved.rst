
.. include:: /Includes.rst.txt

=======================================================
Breaking: #60561 - Default TypoScript Constants Removed
=======================================================

See :issue:`60561`

Description
===========

These default TypoScript constants were dropped:

- :code:`{$_clear}`
- :code:`{$_blackBorderWrap}`
- :code:`{$_tableWrap}`
- :code:`{$_tableWrap_DEBUG}`
- :code:`{$_stdFrameParams}`
- :code:`{$_stdFramesetParams}`


Impact
======

Frontend output may change.


Affected installations
======================

A TYPO3 instance is affected if own TypoScript uses the above mentioned TypoScript constants.


Migration
=========

Either remove usage of the above constants or add a snippet at an early point in TypoScript for backwards compatibility.

.. code-block:: typoscript

      _clear = <img src="clear.gif" width="1" height="1" alt="" />
      _blackBorderWrap = <table border="0" bgcolor="black" cellspacing="0" cellpadding="1"><tr><td> | </td></tr></table>
      _tableWrap = <table border="0" cellspacing="0" cellpadding="0"> | </table>
      _tableWrap_DEBUG = <table border="1" cellspacing="0" cellpadding="0"> | </table>
      _stdFrameParams = frameborder="no" marginheight="0" marginwidth="0" noresize="noresize"
      _stdFramesetParams = 'border="0" framespacing="0" frameborder="no"



.. index:: TypoScript, Frontend
