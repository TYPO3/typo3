..  include:: /Includes.rst.txt

..  _important-106494-1744372580:

=============================================================================================================================================
Important: #106494 - Adapt custom instances of AbstractFormFieldViewHelper to deal with `persistenceManager->getIdentifierByObject()` methods
=============================================================================================================================================

See :issue:`106494`

Description
===========

When dealing with relations to multilingual Extbase entities, these relations should always store
their reference to the "original" (`sys_language_uid=0`) entity, so that later on,
language record overlays can be properly applied.

For this to work in areas like persistence, internally an "identifier" is established that references
these multilingual objects like `[defaultLanguageRecordUid]_[localizedRecordUid]`.

Internally, this identifier should be converted back to only contain/reference the `defaultLanguageRecordUid`.

A bug has been fixed with #106494 to deal with this inside the `<f:form.select>` ViewHelper, which utilized
an `<option value="11_42">` (defaultLanguageRecordUid=11, localizedRecordUid=42), and when using an `<f:form>`
to edit existing records, the currently attached records would NOT get pre-selected.

When such objects with relations were persisted (in frontend management interfaces with Extbase), if the proper
option had not been selected again, the relation would get lost.

Important: Adapt custom ViewHelpers extended from AbstractFormFieldViewHelper or using persistenceManager->getIdentifierByObject()
----------------------------------------------------------------------------------------------------------------------------------

The bug has been fixed, but it is important that if third-party code created custom ViewHelpers based on
:php:`TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper`, these may need adoption too.

Instead of using code like this:

..  code-block:: php
    :caption: Example ViewHelper code utilizing persistenceManager->getIdentifierByObject()

    if ($this->persistenceManager->getIdentifierByObject($valueElement) !== null) {
        return $this->persistenceManager->getIdentifierByObject($valueElement);
    }

the code should be adopted to not rely on `getIdentifierByObject()` but instead:

..  code-block:: php
    :caption: Refactored ViewHelper code preferring an object's getUid() method instead

    if ($this->persistenceManager->getIdentifierByObject($valueElement) !== null) {
        if ($valueElement instanceof DomainObjectInterface) {
            return $valueElement->getUid() ?? $this->persistenceManager->getIdentifierByObject($valueElement);
        }
        return $this->persistenceManager->getIdentifierByObject($valueElement);
    }

This code ensures that retrieving the relational object's UID is done with the overlaid record,
and only falls back to the full identifier, if it's not set, or not an object implementing the Extbase
DomainObjectInterface.

Also note that the abstract's method :php:`convertToPlainValue()` has been fixed to no longer return
a value of format `[defaultLanguageRecordUid]_[localizedRecordUid]` but instead always use the
original record's `->getUid()` return value (=`defaultLanguageRecordUid`).

If this method :php:`convertToPlainValue()` is used in 3rd-party code, make sure this is the
expected result, too.


..  index:: Fluid, Frontend, PHP-API, ext:extbase
