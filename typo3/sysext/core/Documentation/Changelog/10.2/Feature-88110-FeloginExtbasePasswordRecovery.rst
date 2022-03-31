.. include:: /Includes.rst.txt

===================================================
Feature: #88110 - Felogin extbase password recovery
===================================================

See :issue:`88110`

Description
===========

As part of the felogin extbase plugin, a password recovery form has been added.

FE users are able to request a password change via email. A mail with a forgot hash will be send to the requesting user.
If that hash is found valid a reset password form is shown. If all validators are met the users password will be updated.

There is a way to define and override default validators. Configured as default are two validators: NotEmptyValidator and StringLengthValidator.

They can be overridden by overwriting :typoscript:`plugin.tx_felogin_login.settings.passwordValidators`.
Default is as follows:

.. code-block:: typoscript

   passwordValidators {
      10 = TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator
      20 {
         className = TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator
         options {
            minimum = {$styles.content.loginform.newPasswordMinLength}
         }
      }
   }

A custom configuration could look like this:

.. code-block:: typoscript

   passwordValidators {
      10 = TYPO3\CMS\Extbase\Validation\Validator\AlphanumericValidator
      20 {
         className = TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator
         options {
            minimum = {$styles.content.loginform.newPasswordMinLength}
            maximum = 32
         }
      }
      30 = \Vendor\MyExt\Validation\Validator\MyCustomPasswordPolicyValidator
   }

Felogin uses FluidMail. The email_templateName variable in TypoScript is mandatory. Depending on the configuration of
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['format'] the template has to exist as an HTML file, txt file or both. The template
files have to be placed in the same folder.
The template paths can be configured via TypoScript. These paths can either be added to the paths configured in
$GLOBALS['TYPO3_CONF_VARS']['MAIL'] or replace them.

The template paths configuration can be extended as follows:

.. code-block:: typoscript

   plugin.tx_felogin_login {
     settings {
       email {
         templateName = MyRecoveryEmailTemplateName

         layoutRootPaths {
            30 = EXT:myext/Resources/Private/Layouts/Email/
         }
         templateRootPaths {
           30 = EXT:myext/Resources/Private/Templates/Email/
         }
         partialRootPaths {
           30 = EXT:myext/Resources/Private/Partials/Email/
         }
       }
     }
   }

To overwrite the template path configuration provided by felogin, it has to be as follows:

.. code-block:: typoscript

   plugin.tx_felogin_login {
     settings {
       email{
         templateRootPaths {
           20 = EXT:myext/Resources/Private/Templates/Email/
         }
       }
     }
   }


Impact
======

No direct impact. Only used, if feature toggle "felogin.extbase" is explicitly turned on.

.. index:: Database, FlexForm, Fluid, Frontend, TypoScript, ext:felogin
