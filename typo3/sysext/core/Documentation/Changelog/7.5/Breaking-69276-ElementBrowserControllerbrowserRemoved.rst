
.. include:: ../../Includes.txt

=============================================================
Breaking: #69276 - ElementBrowserController::$browser removed
=============================================================

See :issue:`69276`

Description
===========

The `$browser` member variable of `\TYPO3\CMS\Recordlist\Controller\ElementBrowserController` has been removed.


Impact
======

Any third party code accessing `$GLOBAL['SOBE']->browser` will break.


Affected Installations
======================

Installations using third party code, which accesses `$GLOBAL['SOBE']->browser`.


Migration
=========

If the code is extending one of the ElementBrowser tree classes, the protected member variable `$elementBrowser` can
be used to access the underlying ElementBrowser instance.

If your code is using the ElementBrowser tree classes, an instance of `ElementBrowser` has to be injected using the setter.


.. index:: PHP-API, Backend
