..  include:: /Includes.rst.txt

..  _important-105653-1732210472:

=====================================================================================
Important: #105653 - Require a template filename in extbase module template rendering
=====================================================================================

See :issue:`105653`

Description
===========

With the introduction of the FluidAdapter in TYPO3 v13, the dependency between
Fluid and Extbase has been decoupled. As part of this change, the behavior of
the :php:`ModuleTemplate::renderResponse()` and :php:`ModuleTemplate::render()`
methods has been adjusted.

The :php:`$templateFileName` argument is now mandatory for the
:php:`ModuleTemplate::renderResponse()` and :php:`ModuleTemplate::render()`
methods. Previously, if this argument was not provided, the template was
automatically resolved based on the controller and action names. Starting from
TYPO3 13.4, calling these methods with an empty string or without a valid
:php:`$templateFileName` will result in an :php:`InvalidArgumentException`.

Extensions using Extbase backend modules must explicitly provide the
:php:`$templateFileName` when calling these methods. Existing implementations
relying on automatic template resolution need to be updated to prevent
runtime errors.

**Example**:

Before:

.. code-block:: php

   $moduleTemplate->renderResponse();

After:

.. code-block:: php

   $moduleTemplate->renderResponse('MyController/MyAction');

Note, that it is already possible to explicitly provide the
:php:`$templateFileName` in TYPO3 12.4. It is therefore recommended to
implement the new requirement for websites using TYPO3 12.4.

..  index:: Backend, ext:backend
