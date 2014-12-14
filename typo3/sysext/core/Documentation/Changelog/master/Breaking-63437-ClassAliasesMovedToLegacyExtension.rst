==========================================================
Breaking: #63437 - Class aliases moved to legacy extension
==========================================================

Description
===========

With the switch to namespaced classes in TYPO3 CMS 6.0, a class alias mechanism
has been set up to support the old class names as aliases for a while. All those
class aliases are now moved to the dedicated extension "compatibility6". This
extension will be removed from the core with in the future TYPO3 CMS 7 development.

Removing the class aliases from the core results in a significant performance gain
especially during first load with empty caches.


Impact
======

If extensions still rely on old non-namespaced class names, ext:compatibility6 can
be loaded to keep further backwards compatibility for now - with the side effect of
drained performance.

Affected installations
======================

TYPO3 CMS 7 installations need compatibility6 extension loaded if old extensions are
used that are still not adapted to the namespaced core classess.

Migration
=========

During upgrading, the "Extension check" of the install tool may find old extensions that
still rely on old class names and can unload those. The backend may work again to load
extension "compatibility6", or to unload further extensions that rely on old class names.

Another option is to manually set all extensions that rely on old class names to "inactive"
in typo3conf/PackageStates.php, or to set compatibility6 to "active". If a manual change is
done, typo3temp/Cache directory should be deleted afterwards.
