.. role::   typoscript(code)
.. role::   ts(typoscript)
:class:  typoscript

=======================================================
Breaking: #60561 - Default TypoScript Constants Removed
=======================================================

Description
===========

These default TypoScript constants were dropped:

 - :ts:`{$_clear}`
 - :ts:`{$_blackBorderWrap}
 - :ts:`{$_tableWrap}`
 - :ts:`{$_tableWrap_DEBUG}`
 - :ts:`{$_stdFrameParams}`
 - :ts:`{$_stdFramesetParams}`


Impact
======

Frontend output may change.


Affected installations
======================

A TYPO3 instance is affected if own TypoScript uses the above mentioned TypoScript constants.


Migration
=========

Either remove usage of the above constants or add a snippet at an early point in TypoScript for backwards compatibility.

::

  _clear = <img src="clear.gif" width="1" height="1" alt="" />
  _blackBorderWrap = <table border="0" bgcolor="black" cellspacing="0" cellpadding="1"><tr><td> | </td></tr></table>
  _tableWrap = <table border="0" cellspacing="0" cellpadding="0"> | </table>
  _tableWrap_DEBUG = <table border="1" cellspacing="0" cellpadding="0"> | </table>
  _stdFrameParams = frameborder="no" marginheight="0" marginwidth="0" noresize="noresize"
  _stdFramesetParams = 'border="0" framespacing="0" frameborder="no"

..