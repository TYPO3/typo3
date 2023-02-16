.. include:: /Includes.rst.txt

.. _deprecation-100047-1677608959:

=============================================================================
Deprecation: #100047 - PageTsConfig and UserTsConfig must not rely on request
=============================================================================

See :issue:`100047`

Description
===========

Using :typoscript:`request` and function :typoscript:`ip()` in PageTsConfig or
UserTsConfig conditions has been marked as deprecated in TYPO3 v12. Such conditions
will stop working with TYPO3 v13 and will always evaluate to false.

PagesTsConfig and UserTsConfig should not rely on request related data: They should
not check for given arguments or similar: The main reason is that the Backend
:php:`DataHandler` makes heavy use of PageTsConfig, but the DataHandler itself is
not Request aware. The DataHandler (the code logic that updates data in the database
in the backend) can be used and must work in CLI context, so any PageTsConfig that
depends on a given Request is flawed by design since it will never act as expected
in CLI context.

To avoid further issues with the DataHandler in Web and CLI context, TsConfig
related conditions must no longer be request aware.


Impact
======

Using request related conditions in PageTsConfig or UserTsConfig will raise a
deprecation level warning in TYPO3 v12 and will always evaluate to false in
TYPO3 v13.


Affected installations
======================

There may be instances with PageTsConfig using conditions using
request related conditions. These need to look for different solutions
that achieve a similar goal.


Migration
=========

Try to get rid of ip() or request related information in PageTsConfig conditions.

A typical example is highlighting something when a developer is
using the live domain:

.. code-block:: typoscript

    [request.getRequestHost() == 'development.my.site']
        mod.foo = bar
    [end]

Switch to the application context in such cases:

.. code-block:: typoscript

    [applicationContext == "Development"]
        mod.foo = bar
    [end]

There are similar alternatives for other use cases: You can not rely on given
GET / POST arguments anymore, but it should be possible switching to
:typoscript:`backend.user.isAdmin` or similar conditions in most cases, or to
handle according switches within controller classes in PHP.

Relying on request arguments for PageTsConfig conditions is fiddly anyways,
especially when using this for core related controllers: Those are not considered
API and may change anytime. Instead, needs should be dealt with explicitly using
toggles within controllers.


.. index:: TSConfig, NotScanned, ext:backend
