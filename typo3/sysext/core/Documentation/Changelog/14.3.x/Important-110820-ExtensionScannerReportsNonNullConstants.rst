..  include:: /Includes.rst.txt

..  _important-110820-extension-scanner-constants:

=================================================================
Important: #110820 - Extension scanner reports non-null constants
=================================================================

See :issue:`110820`

Description
===========

The Extension Scanner now reports non-null constants passed to argument
positions marked as unused in method or constructor calls. Previously, it
ignored all constant expressions in these positions. A literal :php:`null`
continues to be ignored.

..  index:: Backend, ext:install
