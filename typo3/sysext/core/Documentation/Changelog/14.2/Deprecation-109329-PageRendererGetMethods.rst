..  include:: /Includes.rst.txt

..  _deprecation-109329-1774349266:

=================================================
Deprecation: #109329 - PageRenderer get() methods
=================================================

See :issue:`109329`

Description
===========

The following methods have been deprecated:

* :php:`TYPO3\CMS\Core\Page\PageRenderer->getTitle()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getLanguage()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getDocType()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getHtmlTag()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getHeadTag()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getFavIcon()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getIconMimeType()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getTemplateFile()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getMoveJsFromHeaderToFooter()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getBodyContent()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getInlineLanguageLabels()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getInlineLanguageLabelFiles()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->getMetaTag()`
* :php:`TYPO3\CMS\Core\Page\PageRenderer->removeMetaTag()`
* :php:`TYPO3\CMS\Frontend\ContentObject\AbstractContentObject->getPageRenderer()`


Impact
======

Invoking any of the methods listed above will generate a
deprecation-level log entry in TYPO3 v14. These methods are scheduled
for removal in TYPO3 v15.

From an architectural perspective, the
:php-short:`TYPO3\CMS\Core\Page\PageRenderer` singleton represents
a central yet problematic construct, particularly in TYPO3 frontend
rendering. With the deprecation of
these methods, the :php-short:`TYPO3\CMS\Core\Page\PageRenderer` class loses its
ability to serve as a data source - data can still be added but no longer retrieved.

This change paves the way for refactoring the construct in TYPO3 v15,
including the introduction of a compatibility layer to maintain
backward compatibility.

Affected installations
======================

Instances with extensions invoking one of the methods listed above are
affected. The extension scanner is configured to find consumers, apart from the
generic method names :php:`getTitle()`, :php:`getLanguage()`, and :php:`getPageRenderer()`.

Migration
=========

In practice, there is often little reason to rely on the methods
mentioned above. Most data passed to PageRenderer is handled through
mechanisms that can be intercepted and configured, for example title
and meta tag handling. As a result, the deprecated get() methods do
not have a direct replacement.

A commonly used case is :php:`PageRenderer->getDocType()`, which
determines whether self-closing tags should include a trailing slash
(`/`). This is relevant only in the frontend, as the backend
always uses HTML5. The DocType itself is derived from TypoScript
configuration, which is available as a request attribute.

Before:

..  code-block:: php

    $needsEndingSlash = GeneralUtility::makeInstance(PageRenderer::class)
        ->getDocType()
        ->isXmlCompliant();

After:

..  code-block:: php

    $needsEndingSlash = DocType::createFromRequest($request)
        ->isXmlCompliant();

..  index:: PHP-API, PartiallyScanned, ext:core
