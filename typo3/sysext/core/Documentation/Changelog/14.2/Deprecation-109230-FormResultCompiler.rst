..  include:: /Includes.rst.txt

..  _deprecation-109230-1773404000:

=========================================
Deprecation: #109230 - FormResultCompiler
=========================================

See :issue:`109230`

Description
===========

The class :php:`TYPO3\CMS\Backend\Form\FormResultCompiler` has been
deprecated. The internal implementation of FormEngine has been adjusted to
better separate concerns, especially regarding rendering and asset handling.
This change also removed all internal usages of
:php:`TYPO3\CMS\Backend\Form\FormResultCompiler`,
as it handled more tasks than its name suggested.

Impact
======

Extensions and installations that render FormEngine forms manually rather
than through standard controllers, such as
:php-short:`\TYPO3\CMS\Backend\Controller\EditDocumentController`, and that use
:php-short:`TYPO3\CMS\Backend\Form\FormResultCompiler`, will be affected when the
class is removed in TYPO3 v15.

Affected installations
======================

Installations and extensions using
:php-short:`TYPO3\CMS\Backend\Form\FormResultCompiler` to build FormEngine forms.

Migration
=========

Replace :php-short:`TYPO3\CMS\Backend\Form\FormResultCompiler` with
:php-short:`\TYPO3\CMS\Backend\Form\FormResultFactory` and
:php-short:`\TYPO3\CMS\Backend\Form\FormResultHandler`.

Before:

..  code-block:: php

    use TYPO3\CMS\Backend\Form\NodeFactory;
    use TYPO3\CMS\Backend\Form\FormResultCompiler;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
    $formResultCompiler = GeneralUtility::makeInstance(
        FormResultCompiler::class
    );

    $formResult = $nodeFactory->create($formData)->render();
    $formResultCompiler->mergeResult($formResult);

    // Form HTML markup is accessible in the data array
    $body = $formResult['html'];

After:

..  code-block:: php

    use TYPO3\CMS\Backend\Form\NodeFactory;
    use TYPO3\CMS\Backend\Form\FormResultFactory;
    use TYPO3\CMS\Backend\Form\FormResultHandler;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
    $formResultFactory = GeneralUtility::makeInstance(
        FormResultFactory::class
    );
    $formResultHandler = GeneralUtility::makeInstance(
        FormResultHandler::class
    );

    $formResult = $nodeFactory->create($formData)->render();
    // Convert the raw result array into a FormResult object
    $formResult = $formResultFactory->create($formResult);

    // Use FormResultHandler to pass collected assets (JS, CSS, labels) to PageRenderer
    $formResultHandler->addAssets($formResult);

    // Form HTML markup is accessible in the FormResult DTO
    $body = $formResult->html;

..  index:: Backend, FullyScanned, ext:backend
