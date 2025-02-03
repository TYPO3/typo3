:navigation-title: API

..  include:: /Includes.rst.txt
..  _api:
..  _linkvalidatorapi-AbstractLinktype:
..  _linkvalidatorapi-LabelledLinktypeInterface:
..  _linkvalidatorapi-LinktypeInterface:

=====================================
Public API of the TYPO3 linkvalidator
=====================================

The following classes and interfaces are frequently used by developers
of third party extensions. For the complete API have a look into the code.

*   :php:`\TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype`
*   :php:`\TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface`
*   :php:`\TYPO3\CMS\Linkvalidator\Linktype\LabelledLinktypeInterface`

The following events can be listened to:

*    `BeforeRecordIsAnalyzedEvent <https://docs.typo3.org/permalink/t3coreapi:beforerecordisanalyzedevent>`_
*    `ModifyValidatorTaskEmailEvent <https://docs.typo3.org/permalink/t3coreapi:modifyvalidatortaskemailevent>`_
