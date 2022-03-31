
.. include:: /Includes.rst.txt

=========================================================
Breaking: #39721 - Prototype.js and Scriptaculous removed
=========================================================

See :issue:`39721`

Description
===========

The JavaScript libraries prototype.js and scriptaculous have been removed from the TYPO3 Core.


Impact
======

Any TYPO3 Extension that uses prototype.js or scriptaculous for usage in the Backend or Frontend will not work anymore.

The TypoScript properties `page.javascriptLibs.Prototype` and `page.javascriptLibs.Scriptaculous.*` have been removed
and have no effect anymore, leading to not including prototype in websites where this TypoScript option is set.
This might lead to broken websites when updating.

The shipped Fluid ViewHelper for backend modules does no longer have the according properties anymore and will throw a fatal
error:

.. code-block:: html

	<f:be.container loadPrototype="false" loadScriptaculous="false" scriptaculousModule="someModule,someOtherModule">

Using the PageRenderer directly in any module, and calling one of the related methods will result in a fatal error.

.. code-block:: php

	PageRenderer->setPrototypePath()
	PageRenderer->setScriptaculousPath()
	PageRenderer->getPrototypePath()
	PageRenderer->getScriptaculousPath()
	PageRenderer->loadPrototype()
	PageRenderer->loadScriptaculous()

Including the JavaScript files in a custom extension or custom frontend without using the API will lead to a 404 error
when referencing one of the files.


Affected Installations
======================

Instances that use prototype.js or scriptaculous in the Frontend.

Instances with third-party extensions that

require these libraries or set one of the options in the mentioned ViewHelper.

Instances that link to one of the JavaScript files directly.

Instances that use the PageRenderer API directly and use on of the methods above.


Migration
=========

The preferred substitution is jQuery and RequireJS that are loaded by default in any TYPO3 Backend module, if any
third-party code is needed based on prototype and/or scriptaculous. Alternatively, it is possible to ship a separate
prototype.js and scriptaculous library in a third-party extension if no migration is possible with jQuery.


.. index:: JavaScript, TypoScript, Frontend, Backend
