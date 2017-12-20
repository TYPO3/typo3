.. include:: ../../Includes.txt

======================================================================
Important: #82794 - Added config.sys_language_mode = fallback;3,2,stop
======================================================================

See :issue:`82794`

Description
===========

If a translation (language UID 5) has a TypoScript configuration to
`config.sys_language_mode = content_fallback;3,2` the definition is that if this page is not
available in this translation (language=5) then check if a translation for 3 and after that "2" is
set.

However, if none of the page translations is available, the fallback to "0" always applies.

On a set up like:
- language=0 is german
- language=2 is english-worldwide
- language=3 is english-US
- language=5 is russian

You would not want to fall back to german AT ALL.

It is now possible to define a special keyword called "pageNotFound" to not fall back to
sys_language_uid=0 if any other fallbacks do not work - so a 404 error page is thrown.
`config.sys_language_mode = content_fallback;3,2,pageNotFound`

.. index:: TypoScript, Frontend