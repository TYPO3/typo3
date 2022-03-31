.. include:: /Includes.rst.txt

======================================================
Deprecation: #94209 - Backend ModuleLayout ViewHelpers
======================================================

See :issue:`94209`

Description
===========

The following Fluid ViewHelpers have been deprecated:

*  :html:`be:moduleLayout`
*  :html:`be:moduleLayout.menu`
*  :html:`be:moduleLayout.menuItem`
*  :html:`be:moduleLayout.button.linkButton`
*  :html:`be:moduleLayout.button.shortcutButton`

These ViewHelpers partially mimic their counterparts of the PHP based
:php:`ModuleTemplate` API. They were previously used in backend modules
when the 'doc header' handling was done in Fluid.

The ViewHelpers however relied on knowledge that shouldn't be the scope
of a view component, especially variables like the current action
and controller had to be assigned to the view in many cases.

Additionally, those ViewHelpers were only a sub set of the ModuleTemplate
functionality and created a second API for the same problem domain and
various scenarios like good shortcut implementation and main drop down
state were hard to solve when using these ViewHelpers.


Impact
======

Using these ViewHelpers will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Some extensions with backend modules may use these ViewHelpers. Searching
templates for string :php:`be:moduleLayout` should reveal usages. Extensions
extending the PHP classes are found by the extension scanner as a weak match.


Migration
=========

In general, extensions using these ViewHelpers should switch to using the
PHP API based on class :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate`,
usually initialized by class
:php:`\TYPO3\CMS\Backend\Template\ModuleTemplateFactory` instead.
All Core extensions that render backend
modules provide usage examples and the fluent API is quite straight
forward.

Using the :html:`be:moduleLayout` ViewHelper always rendered FlashMessages
from the queue :php:`'extbase.flashmessages.' . $pluginNamespace` on top of the
content area. You can either use the :html:`f:flashMessages` ViewHelper
or :php:`\TYPO3\CMS\Backend\Template\ModuleTemplate::setFlashMessageQueue()`
as replacements.

For Extbase base backend modules, the 'doc header' should be handled within
controller actions, while the module body is rendered
by the Fluid view component.

In case an extension heavily relies on the deprecated ViewHelpers and the
functionality should be kept with as little work as possible, the easiest
way is of course to simply copy the according ViewHelpers to the extension
directly and to just adapt the namespace in templates accordingly.


.. index:: Backend, Fluid, PartiallyScanned, ext:backend
