..  include:: /Includes.rst.txt

..  _breaking-103910-1737267888:

=========================================================
Breaking: #103910 - Change logout handling in EXT:felogin
=========================================================

See :issue:`103910`

Description
===========

The logout handling has been adjusted to correctly dispatch the PSR-14 event
:php:`\TYPO3\CMS\FrontendLogin\Event\LogoutConfirmedEvent` when a logout
redirect is configured. The :php:`actionUri` variable has been removed, and the
logout template has been updated to reflect this change, including correct use
of the :php:`noredirect` functionality.

Impact
======

The PSR-14 event
:php-short:`\TYPO3\CMS\FrontendLogin\Event\LogoutConfirmedEvent` is now
correctly dispatched when a logout redirect is configured. Additionally, the
:php:`noredirect` parameter is now evaluated during logout.

Affected installations
======================

TYPO3 installations using EXT:felogin with a custom Fluid template for
the logout form.

Migration
=========

The :fluid:`{actionUri}` variable is no longer available and must be removed
from custom templates.

**Before:**

..  code-block:: html
    :caption: Fluid template adjustment (before)

    <!-- Before -->
    <f:form action="login" actionUri="{actionUri}" target="_top" fieldNamePrefix="">

**After:**

..  code-block:: html
    :caption: Fluid template adjustment (after)

    <f:form action="login" target="_top" fieldNamePrefix="">

The evaluation of the :fluid:`{noRedirect}` variable must be added to the
template:

**Before:**

..  code-block:: html
    :caption: Fluid template adjustment for noRedirect (before)

    <div class="felogin-hidden">
        <f:form.hidden name="logintype" value="logout" />
    </div>

**After:**

..  code-block:: html
    :caption: Fluid template adjustment for noRedirect (after)

    <div class="felogin-hidden">
        <f:form.hidden name="logintype" value="logout" />
        <f:if condition="{noRedirect} != ''">
            <f:form.hidden name="noredirect" value="1" />
        </f:if>
    </div>

..  index:: Frontend, NotScanned, ext:felogin
