..  include:: /Includes.rst.txt

..  _breaking-108055-1762951248:

==================================================================
Breaking: #108055 - Removed PageRenderer related hooks and methods
==================================================================

See :issue:`108055`

Description
===========

The removal of Frontend asset concatenation and compression as described in
:ref:`breaking-108055-1762346705` has some impact on PHP API as well, mainly
due to removed code.

The following methods have been removed:

* :php:`\TYPO3\CMS\Core\Page\PageRenderer->disableConcatenateCss()`
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->enableConcatenateCss()`
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->getConcatenateCss()`
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->disableCompressCss()`
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->enableCompressCss()`
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->getCompressCss()`
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->disableConcatenateJavascript()`
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->enableConcatenateJavascript()`
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->getConcatenateJavascript()`
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->disableCompressJavascript()`
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->enableCompressJavascript()`
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->getCompressJavascript()`

The following global configuration registry points have been removed:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['cssConcatenateHandler']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['cssCompressHandler']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['jsConcatenateHandler']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['jsCompressHandler']`

The following hook has been removed:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['minifyJavaScript']`

The following methods changed their signature:

* :php:`\TYPO3\CMS\Core\Page\PageRenderer->addJsInlineCode()` third argument unused
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->addCssInlineBlock()` third argument unused
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->addJsFile()` third and sixth argument unused
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->addJsFooterInlineCode()` third argument unused
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->addJsFooterFile()` third and sixth argument unused
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->addJsLibrary()` fourth and seventh argument unused
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->addJsFooterLibrary()` fourth and seventh argument unused
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->addCssFile()` fifth and eighth argument unused
* :php:`\TYPO3\CMS\Core\Page\PageRenderer->addCssLibrary()` fifth and eighth argument unused

Additionally, registered hooks for
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']`,
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postTransform']`
and :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess']`
no longer receive the array keys :php:`compress` and :php:`excludeFromConcatenation` in the data array keys
:php:`jsFiles`, :php:`jsInline`, :php:`jsLibs`, :php:`cssFiles`, :php:`cssInline` and :php:`cssLibs`.


Impact
======

Calling above listed removed methods will raise PHP fatal errors, registrations for removed hooks are
no longer executed, submitting ignored arguments has no impact anymore and hook consumers receive slightly
different data from TYPO3 core due to removed TypoScript configuration values.


Affected installations
======================

Instances with extensions dealing with low level asset manipulation may be affected. The extension scanner
will find affected extensions when they call removed methods and hooks.


Migration
=========

There is no direct one-to-one migration in this case.

In general, extensions must no longer expect existence of code related to TypoScript configuration options
:typoscript:`config.compressCss`, :typoscript:`config.compressJs`, :typoscript:`config.concatenateCss`,
:typoscript:`config.concatenateJs`, :typoscript:`resource` property :typoscript:`disableCompression` and
:typoscript:`resource` property :typoscript:`excludeFromConcatenation`.

The removed hooks and "handlers" can be turned into listeners of
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']`,
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postTransform']`
and :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess']`,
depending on their need. Existing hook registrations of these three should check if the implementations access
the array keys :php:`compress` and :php:`excludeFromConcatenation` and avoid that. If really needed, affected code may need
to determine TypoScript options from the :php:`$GLOBALS['TYPO3_REQUEST']` Request attribute :php:`frontend.typoscript`
directly. Another alternative is often to avoid the hook usages altogether by turning them into PSR-15 middlewares instead.

..  index:: PHP-API, PartiallyScanned, ext:core
