.. include:: /Includes.rst.txt
getUniqueIdentifier()
'''''''''''''''''''''

Returns a unique identifier of the element. While element identifiers are only unique within one form,
this identifier includes also the identifier of the form itself, and therefore making it "globally" unique.

Signature::

   public function getUniqueIdentifier(): string;

Example:

.. code-block:: yaml

   identifier: exampleForm
   label: 'Simple Contact Form'
   prototype: standard
   type: Form

   renderables:
     -
       identifier: page-1
       label: 'Contact Form'
       type: Page

       renderables:
         -
           identifier: name
           label: 'Name'
           type: Text
           defaultValue: ''

::

   // $formElement->getIdentifier() == 'name'
   $uniqueIdentifier = $formElement->getUniqueIdentifier();
   // $uniqueIdentifier == 'exampleForm-name'
