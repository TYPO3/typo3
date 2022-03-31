.. include:: /Includes.rst.txt

==========================================================================
Feature: #87726 - Extend FrontendLoginController Hook to validate password
==========================================================================

See :issue:`87726`

Description
===========

The Hook :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['password_changed']` is extended to validate the given password.
In the Hook you can set a custom validation message.


Impact
======

You can now use the hook via:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['password_changed'][] = \Your\Namespace\Hooks\MyBeautifulHook::class . '->passwordChanged';

Example implementation:
-----------------------

.. code-block:: php

    public function passwordChanged(array &$params)
    {
        if($params['newPasswordUnencrypted']==='password'){
            $params['passwordValid']=FALSE;
            $params['passwordInvalidMessage']='<p class="text-danger">Do not use password as password</p>';
        }
    }

.. index:: Frontend, ext:felogin, PHP-API
