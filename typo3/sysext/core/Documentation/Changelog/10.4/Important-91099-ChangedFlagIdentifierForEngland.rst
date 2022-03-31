.. include:: /Includes.rst.txt

====================================================================
Important: #91099 - Flag identifier changed for SiteLanguage England
====================================================================

See :issue:`91099`

Description
===========

The flag identifier for England ("england") in the SiteLanguage was broken and resulted
in a broken icon in the backend.
To fix that issue the identifier has been changed ("gb-eng") and results in a proper icon.

If you used this flag identifier in your Frontend setup, double check whether things are
still working as desired.

.. index:: Backend, ext:backend
