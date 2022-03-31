.. include:: /Includes.rst.txt

================================================================
Deprecation: #92947 - TYPO3_MODE and TYPO3_REQUESTTYPE constants
================================================================

See :issue:`92947`

Description
===========

The following global constants have been marked as deprecated:

* :php:`TYPO3_MODE`
* :php:`TYPO3_REQUESTTYPE`
* :php:`TYPO3_REQUESTTYPE_FE`
* :php:`TYPO3_REQUESTTYPE_BE`
* :php:`TYPO3_REQUESTTYPE_CLI`
* :php:`TYPO3_REQUESTTYPE_AJAX`
* :php:`TYPO3_REQUESTTYPE_INSTALL`


Impact
======

The main issues with constants :php:`TYPO3_MODE` and :php:`TYPO3_REQUESTTYPE` is, that they
are NOT constant: Their value depends on the context they are called from. They usually indicate
if a TYPO3 frontend or backend request is executed. Since constants can't be re-defined, this
is a blocker if a single TYPO3 PHP call wants to execute multiple requests to both the
frontend or backend application in one process. This is used by the core testing framework
already and various core features and extensions will benefit from it, too.

There is no other solution than to phase out :php:`TYPO3_MODE` and :php:`TYPO3_REQUESTTYPE`. The
new API to substitute them is only available at a later point during TYPO3 bootstrap, so a couple of
details have to be considered when switching away from usage of those constants in extensions.

Extension developers are highly encouraged to drop usage when making extensions TYPO3 v11 ready.
The constants are only deprecated, their usage is not breaking, yet. To simplify the transition,
the new API has been added to TYPO3 v10, too - it is available since TYPO3 10.4.11. Switching to the
new API early is thus easily possible for extensions that support v10 and v11 in the same version,
without a TYPO3 version check.


Affected Installations
======================

Many extensions use especially the :php:`TYPO3_MODE` constant. The extension scanner will
find the corresponding usages.


Migration
=========

:php:`TYPO3_REQUESTTYPE_*` constants
------------------------------------

* :php:`TYPO3_REQUESTTYPE_FE` - Use :php:`ApplicationType->isFrontend()` instead, see below.
* :php:`TYPO3_REQUESTTYPE_BE` - Use :php:`ApplicationType->isBackend()` instead, see below.
* :php:`TYPO3_REQUESTTYPE_CLI` - Use :php:`Environment::isCli()` instead.
* :php:`TYPO3_REQUESTTYPE_AJAX` - Extensions should barely need this at all. If really required,
  using :php:`strpos($request->getQueryParams()['route'] ?? '', '/ajax/') === 0` could be used
  as alternative to find out if a request is a backend ajax request. A better solution however
  is to refactor consuming code to not depend on this distinction between backend and backend-ajax
  at all - the TYPO3 core may drop this separation at some point in the future, too.
* :php:`TYPO3_REQUESTTYPE_INSTALL` - Extensions should never use this. There is only a small
  number of places in the install tool extensions can extend. Those should have a proper API
  to separate code from other use cases. A specific check for an install tool scope should not
  be required.


:php:`TYPO3_MODE` usage as global script file security gate
-----------------------------------------------------------

TYPO3 still has some extension PHP script files executed in global context without class or
callable encapsulation, namely :file:`ext_localconf.php`, :file:`ext_tables.php` and
files within :file:`Configuration/TCA/Overrides/`. When those files are located within
the public document root of an instance and called via HTTP directly, they may error out and
render error messages. This can be a security risk. To prevent this, those files MUST have a
security gate as first line. This typically looks like::

   defined('TYPO3_MODE') or die();

These calls should be changed to use the new constant :php:`TYPO3` instead. It is simply defined
to :php:`true` in early TYPO3 bootstrap and can be used for this purpose::

   defined('TYPO3') or die();


Other usages of :php:`TYPO3_MODE` and :php:`TYPO3_REQUESTTYPE` in bootstrap script files
----------------------------------------------------------------------------------------

The new API class :php:`ApplicationType` MUST NOT be used in the extension related early bootstrap
script files :file:`ext_localconf.php`, :file:`ext_tables.php` and :file:`Configuration/TCA/*`.

The reason is simple: The frontend and backend :php:`Application` classes are the first objects
within TYPO3 bootstrap that "know" which kind of application is executed. They add this information
to the PSR-7 request object as attribute :php:`applicationType`. The helper class
:php:`ApplicationType` - the main substitution for :php:`TYPO3_MODE` - operates on this. :php:`TCA`
related extension files and :php:`ext_*` script files however are executed *before* the Application
object has been started, and before the request object is set in globals. The information if a frontend
or backend is called does not exist at this point in time, so the helper class :php:`ApplicationType`
can't be used.

This change is in line with a general core bootstrap strategy: A mid-term goal is to have a static
framework state after bootstrap, that does not depend on the executed application type. In the future,
executed code which must change the static state, after the Application object has been set up, will
have better opportunities to reset this state before the Application emits a response. Extensions should
bow to this goal and should drop application related state changes in bootstrap related files.


:php:`TYPO3_MODE` and :php:`TYPO3_REQUESTTYPE` in :file:`Configuration/TCA/*` files
...................................................................................

For extensions which use :php:`TYPO3_MODE` or :php:`TYPO3_REQUESTTYPE` in :php:`TCA` related files in
:file:`Configuration/TCA/*`, the situation is simple: This is not allowed for a while already.
:php:`$GLOBALS['TCA']` state MUST NOT depend on those constants. The :php:`TCA` state is cached after
first call and this cache is used in all applications. If extensions still use those constants in these
files, the :php:`TCA` state depends on whether a first frontend or backend application call is done with
empty caches, which leads to bugs. Extension developers MUST drop this usage in those files.


:php:`TYPO3_MODE` and :php:`TYPO3_REQUESTTYPE` in :file:`ext_localconf.php` and :file:`ext_tables.php` files
............................................................................................................

As outlined above, class :php:`ApplicationType` MUST NOT be used in these files as substitution for
usages of :php:`TYPO3_MODE` and :php:`TYPO3_REQUESTTYPE`. There are a couple of strategies to avoid
this. All of them lead to the situation that framework state changes are always registered and
necessary switches, depending on the executed application, are done at a later point in time.

One example has been realized with core issue :issue:`92848`: This changed the registration of additional
JavaScript for the PageRenderer in backend scope to a hook implementation. The hook has later been
changed to use the :php:`ApplicationType` helper class instead (see below). The idea is that a hook registration
that changes :php:`GLOBALS['TYPO3_CONF_VARS']` or other globals can *always* be done. The decision,
if something should be applied, is determined later, when the hook is called.

Another example is the change for issue :issue:`92952`: It is the same strategy - something is always
registered, the decision if it should actually *do* stuff is postponed to a point when the registered code
is executed.


:php:`TYPO3_MODE` and :php:`TYPO3_REQUESTTYPE` usages in class files
--------------------------------------------------------------------

Some generic extension classes not involved in TYPO3 bootstrap still need to execute different things
if they are executed in frontend or backend scope. A use cases is for instance the need to calculate
different resource paths depending on frontend or backend.

This code should use the new :php:`ApplicationType` class.

Before::

    if (TYPO3_MODE === 'FE') {
        ...
    }

After::

    if (ApplicationType::fromRequest($request)->isFrontend()) {
        ...
    }

This needs the PSR-7 request that is handed over by the Application specific request handlers to single
controllers. Code that needs this switch should be refactored to receive this request object if it is
not available already. However, some extension code (especially core hooks) do not provide the request
object, yet. In those cases, it is ok to fall back to the request object that has been registered as
:php:`$GLOBALS['TYPO3_REQUEST']` by the TYPO3 core. This is always set by the :php:`RequestHandler` that
is called before a controller action is executed. It should be noted that falling back to
:php:`$GLOBALS['TYPO3_REQUEST']` is a technical debt in itself, the TYPO3 core will try to reduce the need
for this fallback over time. A call using this fallback looks like::

    if (ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend())
        ...
    }

As a last use case, there may be low level code executed by a CLI command controller, sometimes using
classes that are also used in frontend or backend scope. Some of these CLI calls do not set up a request
object at all. The core will change this over time with upcoming patches, but some use cases may remain
that are called by CLI directly without a PSR-7 request. The fact that a request object may be missing
and still a detection for frontend or backend application type is needed can lead to this code::

   if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
       && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
   ) {
       ...
   }

.. index:: Backend, CLI, Frontend, PHP-API, PartiallyScanned, ext:core
