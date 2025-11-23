..  include:: /Includes.rst.txt

..  _breaking-108055-1762951248:

==================================================================
Breaking: #108055 - Removed PageRenderer related hooks and methods
==================================================================

See :issue:`108055`

Description
===========

The removal of frontend asset concatenation and compression as described in
:ref:`breaking-108055-1762346705` has some impact on the PHP API as well,
mainly due to removed code.

The following methods have been removed from the class
:php-short:`\TYPO3\CMS\Core\Page\PageRenderer`:

*   :php:`disableConcatenateCss()`
*   :php:`enableConcatenateCss()`
*   :php:`getConcatenateCss()`
*   :php:`disableCompressCss()`
*   :php:`enableCompressCss()`
*   :php:`getCompressCss()`
*   :php:`disableConcatenateJavascript()`
*   :php:`enableConcatenateJavascript()`
*   :php:`getConcatenateJavascript()`
*   :php:`disableCompressJavascript()`
*   :php:`enableCompressJavascript()`
*   :php:`getCompressJavascript()`

The following global configuration registry points have been removed:

*   :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['cssConcatenateHandler']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['cssCompressHandler']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['jsConcatenateHandler']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['FE']['jsCompressHandler']`

The following hook has been removed:

*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_div.php']['minifyJavaScript']`

The following methods of :php-short:`\TYPO3\CMS\Core\Page\PageRenderer` changed
their signature. The noted arguments are now unused:

*   :php:`addJsInlineCode()`: third argument unused
*   :php:`addCssInlineBlock()`: third argument unused
*   :php:`addJsFile()`: third and sixth argument unused
*   :php:`addJsFooterInlineCode()`: third argument unused
*   :php:`addJsFooterFile()`: third and sixth argument unused
*   :php:`addJsLibrary()`: fourth and seventh argument unused
*   :php:`addJsFooterLibrary()`: fourth and seventh argument unused
*   :php:`addCssFile()`: fifth and eighth argument unused
*   :php:`addCssLibrary()`: fifth and eighth argument unused

Additionally, registered hooks for:

*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postTransform']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess']`

no longer receive the array keys :php:`compress` or
:php:`excludeFromConcatenation` inside the following data array keys:

*   :php:`jsFiles`
*   :php:`jsInline`
*   :php:`jsLibs`
*   :php:`cssFiles`
*   :php:`cssInline`
*   :php:`cssLibs`

Impact
======

Calling any of the removed methods listed above will raise PHP fatal errors.
Registrations for removed hooks are no longer executed. Submitting ignored
arguments has no effect anymore, and hook consumers receive slightly different
data from the TYPO3 Core due to removed TypoScript configuration values.

Affected installations
======================

Instances with extensions dealing with low level asset manipulation may be
affected. The extension scanner will find affected extensions when they call
removed methods or hooks.

Migration
=========

There is no direct one-to-one migration in this case.

In general, extensions must no longer expect the existence of code related to
the TypoScript configuration options :typoscript:`config.compressCss`,
:typoscript:`config.compressJs`, :typoscript:`config.concatenateCss`,
:typoscript:`config.concatenateJs`, and the :typoscript:`resource` properties
:typoscript:`disableCompression` and :typoscript:`excludeFromConcatenation`.

The removed hooks and "handlers" can be turned into listeners of:

*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postTransform']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess']`

depending on their needs.

Existing hook registrations of these three should check whether the
implementations access the array keys :php:`compress` and
:php:`excludeFromConcatenation` and avoid doing so. If required, affected code
may need to determine TypoScript options from the :php:`$GLOBALS['TYPO3_REQUEST']`
request attribute :php:`frontend.typoscript` directly.

Another alternative is to avoid hook usage altogether by turning the
implementations into PSR-15 middlewares instead.

..  index:: PHP-API, PartiallyScanned, ext:core
