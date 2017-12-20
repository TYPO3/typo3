
.. include:: ../../Includes.txt

==========================================================
Breaking: #63437 - Class aliases moved to legacy extension
==========================================================

See :issue:`63437`

Description
===========

With the switch to namespaced classes in TYPO3 CMS 6.0, a class alias mechanism
has been set up to support the old class names as aliases for a while. All those
class aliases have been moved to the dedicated extension "compatibility6". This
extension will be removed at some point during the development of TYPO3 CMS 7.

Removing the class aliases from the core results in a significant performance gain
especially during first load with empty caches.


Impact
======

If extensions still rely on old non-namespaced class names, EXT:compatibility6 can
be loaded to keep further backwards compatibility for now - with the side-effect of
drained performance.


Affected installations
======================


TYPO3 CMS 7 installations need EXT:compatibility6 loaded if old extensions are used that
are still not adapted to the namespaced core classes.


Migration
=========

During an upgrade, the "Extension check" of the install tool may find old extensions that
still rely on old class names and can unload those. The backend may work again to load
EXT:compatibility6, or to unload further extensions that rely on old class names.

Another option is to manually set all extensions that rely on old class names to "inactive"
in typo3conf/PackageStates.php, or to set EXT:compatibility6 to "active". If a manual change is
done, typo3temp/Cache directory have to be flushed afterwards.


.. index:: PHP-API
