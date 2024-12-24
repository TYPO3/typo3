..  include:: /Includes.rst.txt

..  _breaking-103910-1737267888:

=========================================================
Breaking: #103910 - Change logout handling in ext:felogin
=========================================================

See :issue:`103910`

Description
===========

The logout handling has been adjusted to correctly dispatch the PSR-14 event
`LogoutConfirmedEvent` when a logout redirect is configured. The
:php:`actionUri` variable has been removed, and the logout template has been
updated to reflect the change, including the correct use of the
:php:`noredirect` functionality.


Impact
======

The PSR-14 event `LogoutConfirmedEvent` is now correctly dispatched, when a
logout redirect is configured. Additionally, the :php:`noredirect` parameter
is now evaluated on logout.


Affected installations
======================

Websites using ext:felogin with a custom Fluid template for the logout form.


Migration
=========

The :php:`{actionUri}` variable is not available any more and should be removed
from the template.

.. code-block:: html

    // Before
    <f:form action="login" actionUri="{actionUri}" target="_top" fieldNamePrefix="">

    // After
    <f:form action="login" target="_top" fieldNamePrefix="">

The evaluation of the :php:`noRedirect` variable must be added to the template.

 .. code-block:: html

    // Before
    <div class="felogin-hidden">
        <f:form.hidden name="logintype" value="logout"/>
    </div>

    // After
    <div class="felogin-hidden">
        <f:form.hidden name="logintype" value="logout"/>
        <f:if condition="{noRedirect}!=''">
            <f:form.hidden name="noredirect" value="1" />
        </f:if>
    </div>

..  index:: Frontend, NotScanned, ext:felogin
