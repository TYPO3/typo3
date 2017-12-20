
.. include:: ../../Includes.txt

==========================================================
Breaking: #55759 - HTML in link titles not working anymore
==========================================================

See :issue:`55759`

Description
===========

By introducing proper handling of double quotes in link titles (TypoLink fields) the processing of the link title is adjusted.
Escaping will be done automatically now.


Impact
======

Existing link titles, which contain HTML escape sequences, will not be shown correctly anymore in Frontend.

Example: A link title `Some &quot;special&quot; title` will be output as `Some &amp;quot;special&amp;quot; title`


Affected Installations
======================

Any installation using links with titles containing HTML escape sequences like `&quot;` or `&gt;`


Migration
=========

Change the affected link titles to contain the plain characters, the correct encoding will be taken care of automatically.

Example: `Some "special" title`

If you need to encode a TypoLink manually in code, use the `TypoLinkCodecService` class, which provides a convenient way
to encode a TypoLink from its fragments.


.. index:: Frontend, Backend
