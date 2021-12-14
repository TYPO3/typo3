.. include:: /Includes.rst.txt
moveElementBefore()
'''''''''''''''''''

Move FormElement $elementToMove before $referenceElement.
Both $elementToMove and $referenceElement must be direct descendants of this Section/Page.

Signature::

   public function moveElementBefore(FormElementInterface $elementToMove, FormElementInterface $referenceElement);
