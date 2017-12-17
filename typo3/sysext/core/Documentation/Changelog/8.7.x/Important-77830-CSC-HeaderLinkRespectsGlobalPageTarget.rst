.. include:: ../../Includes.txt

==========================================================
Important: #77830 - CSC-HeaderLinkRespectsGlobalPageTarget
==========================================================

See :issue:`77830`

Description
===========

Setting the global configuration :typoscript:`lib.parseTarget` was not respected by the header_link field.
Now the configuration is properly applied and might change the output in the frontend.

Impact
======

If the global setting :typoscript:`lib.parseTarget` is set, the field header_link will now respect it.
If in addition the target of header_link is set in a content element, it will take precedence over
:typoscript:`lib.parseTarget`.

.. index:: Frontend, TypoScript
