.. include:: ../Includes.txt


.. _apireference:

=============
API Reference
=============

This chapter is a complete reference of the API of the form framework. It
mainly addresses your concerns as a developer.


.. _apireference-frontendrendering:

Frontend rendering
==================


.. _apireference-frontendrendering-fluidformrenderer:

TYPO3\\CMS\\Form\\Domain\\Renderer\\FluidFormRenderer
-----------------------------------------------------


.. _apireference-frontendrendering-fluidformrenderer-options:

Options
^^^^^^^

The ``FluidFormRenderer`` uses some rendering options which are of particular importance,
as they determine how the form field is resolved to a path in the file system.

All rendering options are retrieved from the ``FormDefinition``, using the ``TYPO3\CMS\Form\Domain\Model\FormDefinition::getRenderingOptions()`` method.


.. _apireference-frontendrendering-fluidformrenderer-options-templaterootpaths:

templateRootPaths
+++++++++++++++++

Used to define several paths for templates, which will be tried in reversed order (the paths are searched from bottom to top).
The first folder where the desired template is found, is used. If the array keys are numeric, they are first sorted and then tried in reversed order.
Within this paths, fluid will search for a file which is named like the ``<formElementTypeIdentifier>``.

For example:

templateRootPaths.10 = EXT:form/Resources/Private/Frontend/Templates/
$renderable->getType() == 'Form'
Expected template file: EXT:form/Resources/Private/Frontend/Templates/Form.html

Only the root element (``FormDefinition``) has to be a template file. All child form elements are partials. By default, the root element is called ``Form``.

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formElementsDefinition:
               Form:
                 renderingOptions:
                   templateRootPaths:
                     10: 'EXT:form/Resources/Private/Frontend/Templates/'


.. _apireference-frontendrendering-fluidformrenderer-options-layoutrootpaths:

layoutRootPaths
+++++++++++++++

Used to define several paths for layouts, which will be tried in reversed order (the paths are searched from bottom to top).
The first folder where the desired layout is found, is used. If the array keys are numeric, they are first sorted and then tried in reversed order.

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formElementsDefinition:
               Form:
                 renderingOptions:
                   layoutRootPaths:
                     10: 'EXT:form/Resources/Private/Frontend/Layouts/'


.. _apireference-frontendrendering-fluidformrenderer-options-partialrootpaths:

partialRootPaths
++++++++++++++++

Used to define several paths for partials, which will be tried in reversed order. The first folder where the desired partial is found, is used.
The keys of the array define the order.

Within this paths, fluid will search for a file which is named like the ``<formElementTypeIdentifier>``.

For example:

templateRootPaths.10 = EXT:form/Resources/Private/Frontend/Partials/
$renderable->getType() == 'Text'
Expected template file: EXT:form/Resources/Private/Frontend/Partials/Text.html

There is a setting available to set a custom partial name. Please read the section :ref:`templateName<apireference-frontendrendering-fluidformrenderer-options-templatename>`.

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formElementsDefinition:
               Form:
                 renderingOptions:
                   partialRootPaths:
                     10: 'EXT:form/Resources/Private/Frontend/Partials/'


.. _apireference-frontendrendering-fluidformrenderer-options-templatename:

templateName
++++++++++++

By default, the renderable type will be taken as the name for the partial.

For example:

partialRootPaths.10 = EXT:form/Resources/Private/Frontend/Partials/
$renderable->getType() == 'Text'
Expected partial file: EXT:form/Resources/Private/Frontend/Partials/Text.html

Set ``templateName`` to define a custom name which should be used instead.

For example:

$renderable->getTemplateName() == 'Text'
$renderable->getType() = Foo
Expected partial file: EXT:form/Resources/Private/Frontend/Partials/Text.html

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formElementsDefinition:
               Foo:
                 renderingOptions:
                   templateName: 'Text'


.. _apireference-frontendrendering-renderviewHelper:

"render" viewHelper
-------------------

.. _apireference-frontendrendering-renderviewHelper-arguments:

Arguments
^^^^^^^^^

.. _apireference-frontendrendering-renderviewHelper-factoryclass:

factoryClass
++++++++++++

A class name of a ``FormFactory``.
This factory is used to create the ``TYPO3\CMS\Form\Domain\Model\FormDefinition`` which is the ``form definition`` Domain Model.
If no ``factoryClass`` argument is passed, the factory supplied by EXT:form ``TYPO3\CMS\Form\ Domain\Factory\ArrayFormFactory`` is used.
Another factory class is required if the form is to be generated programmatically.
To do this you must implement your own ``FormFactory`` in which your own form is generated programmatically and passes this class name to the ViewHelper.
This then renders the form.

.. code-block:: html

   <formvh:render factoryClass="VENDOR\MySitePackage\Domain\Factory\CustomFormFactory" />


.. _apireference-frontendrendering-renderviewHelper-persistenceidentifier:

persistenceIdentifier
+++++++++++++++++++++

The ``form definition`` to be found under ``persistenceIdentifier``.
The PersistenceManager now loads the ``form definition`` which is found under ``persistenceIdentifier`` and passes this configuration to the ``factoryClass``.
In this case, the ``factoryClass`` will be given an empty configuration array (if ``overrideConfiguration`` is not specified).

.. code-block:: html

   <formvh:render persistenceIdentifier="EXT:my_site_package/Resources/Private/Forms/SimpleContactForm.yaml" />


.. _apireference-frontendrendering-renderviewHelper-overrideconfiguration:

overrideConfiguration
+++++++++++++++++++++

A configuration to be superimposed can be entered here.
If a ``persistenceIdentifier`` is specified, the ``form definition`` which is found under ``persistenceIdentifier`` is loaded.
This configuration is then superimposed with ``overrideConfiguration``. This configuration is then passed to the ``factoryClass``.
If no ``persistenceIdentifier`` is specified, ``overrideConfiguration`` is passed directly to the ``factoryClass``.
This way a configuration can be given to a ``factoryClass`` implementation.


.. _apireference-frontendrendering-renderviewHelper-prototypename:

prototypeName
+++++++++++++

The name of the prototype, on which basis the ``factoryClass`` should create the form.
If nothing is specified, the configuration (``form definition`` or ``overrideConfiguration``) is searched for the prototy name.
If no specification exists, the standard prototype ``standard`` is used.



.. _apireference-frontendrendering-programmatically:

Build forms programmatically
----------------------------

Implement a ``FormFactory`` and build the form::

   declare(strict_types = 1);
   namespace VENDOR\MySitePackage\Domain\Factory;

   use TYPO3\CMS\Core\Utility\GeneralUtility;
   use TYPO3\CMS\Extbase\Object\ObjectManager;
   use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
   use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
   use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
   use TYPO3\CMS\Form\Domain\Factory\AbstractFormFactory;
   use TYPO3\CMS\Form\Domain\Model\FormDefinition;

   class CustomFormFactory extends AbstractFormFactory
   {

       /**
        * Build a FormDefinition.
        * This example build a FormDefinition manually,
        * so $configuration and $prototypeName are unused.
        *
        * @param array $configuration
        * @param string $prototypeName
        * @return FormDefinition
        */
       public function build(array $configuration, string $prototypeName = null): FormDefinition
       {
           $prototypeName = 'standard';
           $configurationService = GeneralUtility::makeInstance(ObjectManager::class)->get(ConfigurationService::class);
           $prototypeConfiguration = $configurationService->getPrototypeConfiguration($prototypeName);

           $form = GeneralUtility::makeInstance(ObjectManager::class)->get(FormDefinition::class, 'MyCustomForm', $prototypeConfiguration);
           $form->setRenderingOption('controllerAction', 'index');

           $page1 = $form->createPage('page1');
           $name = $page1->createElement('name', 'Text');
           $name->setLabel('Name');
           $name->addValidator(GeneralUtility::makeInstance(ObjectManager::class)->get(NotEmptyValidator::class));

           $page2 = $form->createPage('page2');
           $message = $page2->createElement('message', 'Textarea');
           $message->setLabel('Message');
           $message->addValidator(GeneralUtility::makeInstance(ObjectManager::class)->get(StringLengthValidator::class, ['minimum' => 5, 'maximum' => 20]));

           // Creating a RadioButton/MultiCheckbox
           $page3 = $form->createPage('page3');
           $radio = $page3->createElement('checkbox', 'RadioButton');
           $radio->setProperty('options', ['value1' => 'Label1', 'value2' => 'Label2']);
           $radio->setLabel('My Radio ...');

           $form->createFinisher('EmailToSender', [
               'subject' => 'Hello',
               'recipientAddress' => 'foo@example.com',
               'senderAddress' => 'bar@example.com',
           ]);

           $this->triggerFormBuildingFinished($form);
           return $form;
       }
   }

Use this form within your fluid template.

.. code-block:: html

   <formvh:render factoryClass="VENDOR\MySitePackage\Domain\Factory\CustomFormFactory" />


.. _apireference-frontendrendering-programmatically-commonapimethods:

Common API Methods
^^^^^^^^^^^^^^^^^^


.. _apireference-frontendrendering-programmatically-commonapimethods-createpage:

TYPO3\\CMS\\Form\\Domain\\Model\\FormDefinition::createPage()
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Create a page with the given $identifier and attach this page to the form.

- Create Page object based on the given $typeName
- set defaults inside the Page object
- attach Page object to this form
- return the newly created Page object

Signature::

   public function createPage(string $identifier, string $typeName = 'Page'): Page;


.. _apireference-frontendrendering-programmatically-commonapimethods-createfinisher:

TYPO3\\CMS\\Form\\Domain\\Model\\FormDefinition::createFinisher()
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Create a finisher with the given $identifier and given $options and attach this finisher to the form.

Signature::

   public function createFinisher(string $finisherIdentifier, array $options = []): FinisherInterface;


.. _apireference-frontendrendering-programmatically-commonapimethods-page-createelement:

TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\Page::createElement()
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Create a form element with the given $identifier and attach it to the page.

- Create Form Element object based on the given $typeName
- set defaults inside the Form Element (based on the parent form's field defaults)
- attach Form Element to the Page
- return the newly created Form Element object

Signature::

   public function createElement(string $identifier, string $typeName): FormElementInterface;


.. _apireference-frontendrendering-programmatically-commonapimethods-section-createelement:

TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\Section::createElement()
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Create a form element with the given $identifier and attach it to the section.

- Create Form Element object based on the given $typeName
- set defaults inside the Form Element (based on the parent form's field defaults)
- attach Form Element to the Section
- return the newly created Form Element object

Signature::

   public function createElement(string $identifier, string $typeName): FormElementInterface;


.. _apireference-frontendrendering-programmatically-commonapimethods-abstractrenderable-createvalidator:

TYPO3\\CMS\\Form\\Domain\\Model\\Renderable\\AbstractFormElement::createValidator()
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Create a validator for the element.
Mainly possible for

- TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\AdvancedPassword
- TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\GenericFormElement
- TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\DatePicker
- TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\FileUpload

Signature::

   public function createValidator(string $validatorIdentifier, array $options = []);


.. _apireference-frontendrendering-programmatically-commonapimethods-initializeformelement:

initializeFormElement()
+++++++++++++++++++++++

Will be called as soon as the element is added to a form.
Possible for

- TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\Section
- TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\AdvancedPassword
- TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\GenericFormElement
- TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\DatePicker
- TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\FileUpload

Signature::

   public function initializeFormElement();


You can use this method to prefill form element data for example from database tables.
All the classes you can see above extends from the ``TYPO3\CMS\Form\Domain\Model\FormElement\AbstractFormElement``.
``AbstractFormElement`` implements this method like this::

   public function initializeFormElement()
   {
       if (
           isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement'])
           && is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement'])
       ) {
           foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement'] as $className) {
               $hookObj = GeneralUtility::makeInstance($className);
               if (method_exists($hookObj, 'initializeFormElement')) {
                   $hookObj->initializeFormElement(
                       $this
                   );
               }
           }
       }
   }

If you extend you custom implementation from ``AbstractFormElement`` (and you should do this),
it enables you to override the 'initializeFormElement' method within your custom implementation class.
If you do not call the parents 'initializeFormElement' then no hook will be thrown.

If your use case for a custom form element implementation means that you only want to initialize you form element
programmatically (e.g to get databasedata) and no other special things are to do, you might prefer the hook.
You only need a class which connects to this hook. Then detect the form element you wish to initialize.


.. _apireference-frontendrendering-programmatically-apimethods:

Further API Methods
^^^^^^^^^^^^^^^^^^^


.. _apireference-frontendrendering-programmatically-apimethods-formruntime:

TYPO3\\CMS\\Form\\Domain\\Model\\FormRuntime
++++++++++++++++++++++++++++++++++++++++++++


.. _apireference-frontendrendering-programmatically-apimethods-formruntime-overridecurrentpage:

overrideCurrentPage()
'''''''''''''''''''''

Override the current page taken from the request, rendering the page with index $pageIndex instead.
This is typically not needed in production code.
You might prefer the hook :ref:`afterInitializeCurrentPage <apireference-frontendrendering-runtimemanipulation-hooks-afterinitializecurrentpage>`

Signature::

   public function overrideCurrentPage(int $pageIndex);

Example::

   $form = $formDefinition->bind($this->request, $this->response);
   $form->overrideCurrentPage($pageIndex);


.. _apireference-frontendrendering-programmatically-apimethods-formruntime-render:

render()
''''''''

Render the form.

Signature::

   public function render();


.. _apireference-frontendrendering-programmatically-apimethods-formruntime-getidentifier:
.. include:: RootRenderableInterface/getIdentifier.rst

.. _apireference-frontendrendering-programmatically-apimethods-formruntime-getrequest:

getRequest()
''''''''''''

Get the request this object is bound to.
This is mostly relevant inside Finishers, where you f.e. want to redirect the user to another page.

Signature::

   public function getRequest(): Request;


.. _apireference-frontendrendering-programmatically-apimethods-formruntime-getresponse:

getResponse()
'''''''''''''

Get the response this object is bound to.
This is mostly relevant inside Finishers, where you f.e. want to set response headers or output content.

Signature::

   public function getResponse(): Response;


.. _apireference-frontendrendering-programmatically-apimethods-formruntime-getcurrentpage:

getCurrentPage()
''''''''''''''''

Returns the currently selected page.

Signature::

   public function getCurrentPage(): Page;


.. _apireference-frontendrendering-programmatically-apimethods-formruntime-getpreviouspage:

getPreviousPage()
'''''''''''''''''

Returns the previous page of the currently selected one or NULL if there is no previous page.

Signature::

   public function getPreviousPage();


.. _apireference-frontendrendering-programmatically-apimethods-formruntime-getnextpage:

getNextPage()
'''''''''''''

Returns the next page of the currently selected one or NULL if there is no next page.

Signature::

   public function getNextPage();


.. _apireference-frontendrendering-programmatically-apimethods-formruntime-gettype:
.. include:: RootRenderableInterface/getType.rst


.. _apireference-frontendrendering-programmatically-apimethods-formruntime-getelementvalue:

getElementValue()
'''''''''''''''''

Returns the value of the specified element.

Signature::

   public function getElementValue(string $identifier);


.. _apireference-frontendrendering-programmatically-apimethods-formruntime-getpages:

getPages()
''''''''''

Return the form's pages in the correct order.

Signature::

   public function getPages(): array;


.. _apireference-frontendrendering-programmatically-apimethods-formruntime-getrenderingoptions:
.. include:: RootRenderableInterface/getRenderingOptions.rst

.. _apireference-frontendrendering-programmatically-apimethods-formruntime-getrendererclassname:
.. include:: RootRenderableInterface/getRendererClassName.rst

.. _apireference-frontendrendering-programmatically-apimethods-formruntime-getlabel:
.. include:: RootRenderableInterface/getLabel.rst

.. _apireference-frontendrendering-programmatically-apimethods-formruntime-gettemplatename:
.. include:: RenderableInterface/getTemplateName.rst

.. _apireference-frontendrendering-programmatically-apimethods-formruntime-getformdefinition:

getFormDefinition()
'''''''''''''''''''

Get the underlying form definition from the runtime.

Signature::

   public function getFormDefinition(): FormDefinition;


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition:

TYPO3\\CMS\\Form\\Domain\\Model\\FormDefinition
+++++++++++++++++++++++++++++++++++++++++++++++

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-addpage:

addPage()
'''''''''

Add a new page at the end of the form.
Instead of this method, you should use ``createPage`` instead.

Signature::

   public function addPage(Page $page);


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-createpage:

createPage()
''''''''''''

Create a page with the given $identifier and attach this page to the form.

- Create Page object based on the given $typeName
- set defaults inside the Page object
- attach Page object to this form
- return the newly created Page object

Signature::

   public function createPage(string $identifier, string $typeName = 'Page'): Page;


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-getpages:

getPages()
''''''''''

Return the form's pages in the correct order.

Signature::

   public function getPages(): array;


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-haspagewithindex:

hasPageWithIndex()
''''''''''''''''''

Check whether a page with the given $index exists.

Signature::

   public function hasPageWithIndex(int $index): bool;


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-getpagebyindex:

getPageByIndex()
''''''''''''''''

Get the page with the passed index. The first page has index zero.
If page at $index does not exist, an exception is thrown.

Signature::

   public function getPageByIndex(int $index);


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-addfinisher:

addFinisher()
'''''''''''''

Adds the specified finisher to the form.
Instead of this method, you should use ``createFinisher`` instead.

Signature::

   public function addFinisher(FinisherInterface $finisher);


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-createfinisher:

createFinisher()
''''''''''''''''

Create a finisher with the given $identifier and given $options and attach this finisher to the form.

Signature::

   public function createFinisher(string $finisherIdentifier, array $options = []): FinisherInterface;

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-getfinishers:

getFinishers()
''''''''''''''

Gets all finishers of the form.

Signature::

   public function getFinishers(): array;


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-getelementbyidentifier:

getElementByIdentifier()
''''''''''''''''''''''''

Get a form element by its identifier.
If identifier does not exist, returns NULL.

Signature::

   public function getElementByIdentifier(string $elementIdentifier);


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-movepageafter:

movePageAfter()
'''''''''''''''

Move $pageToMove after $referencePage.

Signature::

   public function movePageAfter(Page $pageToMove, Page $referencePage);


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-removepage:

removePage()
''''''''''''

Remove $pageToRemove from the form.

Signature::

   public function removePage(Page $pageToRemove);


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-bind:

bind()
''''''

Bind the current request and response to this form instance, effectively creating a new "instance" of the Form.

Signature::

   public function bind(Request $request, Response $response): FormRuntime;


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-getprocessingrule:

getProcessingRule()
'''''''''''''''''''

Get the processing rule which contains information for property mappings and validations.

Signature::

   public function getProcessingRule(string $propertyPath): ProcessingRule;


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-gettype:
.. include:: RootRenderableInterface/getType.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-getidentifier:
.. include:: RootRenderableInterface/getIdentifier.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-setidentifier:
.. include:: AbstractRenderable/setIdentifier.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-setoptions:
.. include:: AbstractRenderable/setOptions.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-addvalidator:
.. include:: FormElementInterface/addValidator.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-setdatatype:
.. include:: FormElementInterface/setDataType.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-getrendererclassname:
.. include:: RootRenderableInterface/getRendererClassName.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-setrendererclassname:

setRendererClassName()
''''''''''''''''''''''

Set the renderer class name.

Signature::

   public function setRendererClassName(string $rendererClassName);


.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-getrenderingoptions:
.. include:: RootRenderableInterface/getRenderingOptions.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-setrenderingoption:
.. include:: FormElementInterface/setRenderingOption.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-getparentrenderable:
.. include:: RenderableInterface/getParentRenderable.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-setparentrenderable:
.. include:: RenderableInterface/setParentRenderable.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-getrootform:
.. include:: AbstractRenderable/getRootForm.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-getlabel:
.. include:: RootRenderableInterface/getLabel.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-setlabel:
.. include:: AbstractRenderable/setLabel.rst

.. _apireference-frontendrendering-programmatically-apimethods-formdefinition-gettemplatename:
.. include:: RenderableInterface/getTemplateName.rst


.. _apireference-frontendrendering-programmatically-apimethods-page:

TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\Page
+++++++++++++++++++++++++++++++++++++++++++++++++++

.. _apireference-frontendrendering-programmatically-apimethods-page-getelements:
.. include:: AbstractSection/getElements.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-getelementsrecursively:
.. include:: AbstractSection/getElementsRecursively.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-addelement:
.. include:: AbstractSection/addElement.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-createelement:

createElement()
'''''''''''''''

Create a form element with the given $identifier and attach it to the page.

- Create Form Element object based on the given $typeName
- set defaults inside the Form Element (based on the parent form's field defaults)
- attach Form Element to the Page
- return the newly created Form Element object

Signature::

   public function createElement(string $identifier, string $typeName): FormElementInterface;


.. _apireference-frontendrendering-programmatically-apimethods-page-moveelementbefore:
.. include:: AbstractSection/moveElementBefore.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-moveelementafter:
.. include:: AbstractSection/moveElementAfter.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-removeelement:
.. include:: AbstractSection/removeElement.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-gettype:
.. include:: RootRenderableInterface/getType.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-getidentifier:
.. include:: RootRenderableInterface/getIdentifier.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-setidentifier:
.. include:: AbstractRenderable/setIdentifier.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-setoptions:
.. include:: AbstractRenderable/setOptions.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-addvalidator:
.. include:: FormElementInterface/addValidator.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-createvalidator:
.. include:: FormElementInterface/createValidator.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-setdatatype:
.. include:: FormElementInterface/setDataType.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-getrendererclassname:
.. include:: RootRenderableInterface/getRendererClassName.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-getrenderingoptions:
.. include:: RootRenderableInterface/getRenderingOptions.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-setrenderingoption:
.. include:: FormElementInterface/setRenderingOption.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-getparentrenderable:
.. include:: RenderableInterface/getParentRenderable.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-setparentrenderable:
.. include:: RenderableInterface/setParentRenderable.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-getrootform:
.. include:: AbstractRenderable/getRootForm.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-getlabel:
.. include:: RootRenderableInterface/getLabel.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-setlabel:
.. include:: AbstractRenderable/setLabel.rst

.. _apireference-frontendrendering-programmatically-apimethods-page-gettemplatename:
.. include:: RenderableInterface/getTemplateName.rst


.. _apireference-frontendrendering-programmatically-apimethods-section:

TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\Section
++++++++++++++++++++++++++++++++++++++++++++++++++++++

.. _apireference-frontendrendering-programmatically-apimethods-section-initializeformelement:
.. include:: FormElementInterface/initializeFormElement.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-getuniqueidentifier:
.. include:: FormElementInterface/getUniqueIdentifier.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-setproperty:
.. include:: FormElementInterface/setProperty.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-getproperties:
.. include:: FormElementInterface/getProperties.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-isrequired:
.. include:: FormElementInterface/isRequired.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-getelements:
.. include:: AbstractSection/getElements.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-getelementsrecursively:
.. include:: AbstractSection/getElementsRecursively.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-addelement:
.. include:: AbstractSection/addElement.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-createelement:

createElement()
'''''''''''''''

Create a form element with the given $identifier and attach it to the section.

- Create Form Element object based on the given $typeName
- set defaults inside the Form Element (based on the parent form's field defaults)
- attach Form Element to the Section
- return the newly created Form Element object

Signature::

   public function createElement(string $identifier, string $typeName): FormElementInterface;


.. _apireference-frontendrendering-programmatically-apimethods-section-moveelementbefore:
.. include:: AbstractSection/moveElementBefore.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-moveelementafter:
.. include:: AbstractSection/moveElementAfter.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-removeelement:
.. include:: AbstractSection/removeElement.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-gettype:
.. include:: RootRenderableInterface/getType.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-getidentifier:
.. include:: RootRenderableInterface/getIdentifier.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-setidentifier:
.. include:: AbstractRenderable/setIdentifier.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-setoptions:
.. include:: AbstractRenderable/setOptions.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-addvalidator:
.. include:: FormElementInterface/addValidator.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-createvalidator:
.. include:: FormElementInterface/createValidator.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-setdatatype:
.. include:: FormElementInterface/setDataType.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-getrendererclassname:
.. include:: RootRenderableInterface/getRendererClassName.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-getrenderingoptions:
.. include:: RootRenderableInterface/getRenderingOptions.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-setrenderingoption:
.. include:: FormElementInterface/setRenderingOption.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-getparentrenderable:
.. include:: RenderableInterface/getParentRenderable.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-setparentrenderable:
.. include:: RenderableInterface/setParentRenderable.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-getrootform:
.. include:: AbstractRenderable/getRootForm.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-getlabel:
.. include:: RootRenderableInterface/getLabel.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-setlabel:
.. include:: AbstractRenderable/setLabel.rst

.. _apireference-frontendrendering-programmatically-apimethods-section-gettemplatename:
.. include:: RenderableInterface/getTemplateName.rst


.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement:

TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\AbstractFormElement
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

The following classes extends from ``AbstractFormElement`` and therefore contain the following API methods.

- TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\AdvancedPassword
- TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\GenericFormElement
- TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\DatePicker
- TYPO3\\CMS\\Form\\Domain\\Model\\FormElements\\FileUpload


.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-initializeformelement:
.. include:: FormElementInterface/initializeFormElement.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-getuniqueidentifier:
.. include:: FormElementInterface/getUniqueIdentifier.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-getdefaultvalue:
.. include:: FormElementInterface/getDefaultValue.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-setdefaultvalue:
.. include:: FormElementInterface/setDefaultValue.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-setproperty:
.. include:: FormElementInterface/setProperty.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-getproperties:
.. include:: FormElementInterface/getProperties.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-isrequired:
.. include:: FormElementInterface/isRequired.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-gettype:
.. include:: RootRenderableInterface/getType.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-getidentifier:
.. include:: RootRenderableInterface/getIdentifier.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-setidentifier:
.. include:: AbstractRenderable/setIdentifier.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-setoptions:
.. include:: AbstractRenderable/setOptions.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-addvalidator:
.. include:: FormElementInterface/addValidator.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-createvalidator:
.. include:: FormElementInterface/createValidator.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-setdatatype:
.. include:: FormElementInterface/setDataType.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-getrendererclassname:
.. include:: RootRenderableInterface/getRendererClassName.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-getrenderingoptions:
.. include:: RootRenderableInterface/getRenderingOptions.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-setrenderingoption:
.. include:: FormElementInterface/setRenderingOption.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-getparentrenderable:
.. include:: RenderableInterface/getParentRenderable.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-setparentrenderable:
.. include:: RenderableInterface/setParentRenderable.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-getrootform:
.. include:: AbstractRenderable/getRootForm.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-getlabel:
.. include:: RootRenderableInterface/getLabel.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-setlabel:
.. include:: AbstractRenderable/setLabel.rst

.. _apireference-frontendrendering-programmatically-apimethods-abstractformelement-gettemplatename:
.. include:: RenderableInterface/getTemplateName.rst


.. _apireference-frontendrendering-programmatically-apimethods-abstractfinisher:

TYPO3\\CMS\\Form\\Domain\\Finishers\\AbstractFinisher
+++++++++++++++++++++++++++++++++++++++++++++++++++++

The following classes extends from ``AbstractFinisher`` and therefore contain the following API methods.

- TYPO3\\CMS\\Form\\Domain\\Finishers\\ClosureFinisher
- TYPO3\\CMS\\Form\\Domain\\Finishers\\ConfirmationFinisher
- TYPO3\\CMS\\Form\\Domain\\Finishers\\DeleteUploadsFinisher
- TYPO3\\CMS\\Form\\Domain\\Finishers\\EmailFinisher
- TYPO3\\CMS\\Form\\Domain\\Finishers\\FlashMessageFinisher
- TYPO3\\CMS\\Form\\Domain\\Finishers\\RedirectFinisher
- TYPO3\\CMS\\Form\\Domain\\Finishers\\SaveToDatabaseFinisher


.. _apireference-frontendrendering-programmatically-apimethods-abstractfinisher-execute:

execute()
'''''''''

Executes the finisher. ``AbstractFinisher::execute()`` call ``$this->executeInternal()`` at the end. Own finisher
implementations which extends from  ``AbstractFinisher:`` must start their own logic within ``executeInternal()``.

Signature::

   public function execute(FinisherContext $finisherContext);


.. _apireference-frontendrendering-programmatically-apimethods-abstractfinisher-setoptions:

setOptions()
''''''''''''

Set the finisher options. Instead of directly accessing them, you should rather use ``parseOption()``.

Signature::

   public function setOptions(array $options);


.. _apireference-frontendrendering-programmatically-apimethods-abstractfinisher-setoption:

setOption()
'''''''''''

Sets a single finisher option.

Signature::

   public function setOption(string $optionName, $optionValue);


.. _apireference-frontendrendering-programmatically-apimethods-abstractfinisher-parseoption:

parseOption()
'''''''''''''

Please read :ref:`Accessing finisher options<concepts-finishers-customfinisherimplementations-accessingoptions>`

Signature::

   protected function parseOption(string $optionName);


.. _apireference-frontendrendering-programmatically-apimethods-finishercontext:

TYPO3\\CMS\\Form\\Domain\\Finishers\\FinisherContext
++++++++++++++++++++++++++++++++++++++++++++++++++++

.. _apireference-frontendrendering-programmatically-apimethods-finishercontext-cancel:

cancel()
''''''''

Cancels the finisher invocation after the current finisher.

Signature::

   public function cancel();


.. _apireference-frontendrendering-programmatically-apimethods-finishercontext-getformruntime:

getFormRuntime()
''''''''''''''''

The Form Runtime that is associated with the current finisher.

Signature::

   public function getFormRuntime(): FormRuntime;


.. _apireference-frontendrendering-programmatically-apimethods-finishercontext-getformvalues:

getFormValues()
'''''''''''''''

The values of the submitted form (after validation and property mapping).

Signature::

   public function getFormValues(): array;


.. _apireference-frontendrendering-programmatically-apimethods-finishercontext-getcontrollercontext:

getControllerContext()
''''''''''''''''''''''

Returns the current ControllerContext.

Signature::

   public function getControllerContext(): ControllerContext;


.. _apireference-frontendrendering-programmatically-apimethods-finishercontext-getfinishervariableprovider:

getFinisherVariableProvider()
'''''''''''''''''''''''''''''

Returns the current FinisherVariableProvider.

Signature::

   public function getFinisherVariableProvider(): FinisherVariableProvider;


.. _apireference-frontendrendering-programmatically-apimethods-finishervariableprovider:

TYPO3\\CMS\\Form\\Domain\\Finishers\\FinisherVariableProvider
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Please read :ref:`Share data between finishers<concepts-finishers-customfinisherimplementations-finishercontext-sharedatabetweenfinishers>`

.. _apireference-frontendrendering-programmatically-apimethods-finishervariableprovider-add:

add()
'''''

Add a variable to the finisher variable provider.
In case the value is already inside, it is silently overridden.

Signature::

   public function add(string $finisherIdentifier, string $key, $value);


.. _apireference-frontendrendering-programmatically-apimethods-finishervariableprovider-get:

get()
'''''

Gets a variable from the finisher variable provider.

Signature::

   public function get(string $finisherIdentifier, string $key, $default = null);


.. _apireference-frontendrendering-programmatically-apimethods-finishervariableprovider-exists:

exists()
''''''''

Determine whether there is a variable stored for the given key.

Signature::

   public function exists($finisherIdentifier, $key): bool;


.. _apireference-frontendrendering-programmatically-apimethods-finishervariableprovider-remove:

remove()
''''''''

Remove a value from the finisher variable provider.

Signature::

   public function remove(string $finisherIdentifier, string $key);


.. _apireference-frontendrendering-programmatically-apimethods-configurationservice:

TYPO3\\CMS\\Form\\Domain\\Configuration\\ConfigurationService
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

.. _apireference-frontendrendering-programmatically-apimethods-configurationservice-getprototypeconfiguration:

getPrototypeConfiguration()
'''''''''''''''''''''''''''

Get the configuration for a given $prototypeName

Signature::

   public function getPrototypeConfiguration(string $prototypeName): array;


.. _apireference-frontendrendering-programmatically-apimethods-abstractformfactory:

TYPO3\\CMS\\Form\\Domain\\Factory\\AbstractFormFactory
++++++++++++++++++++++++++++++++++++++++++++++++++++++

.. _apireference-frontendrendering-programmatically-apimethods-abstractformfactory-triggerformbuildingfinished:

triggerFormBuildingFinished()
'''''''''''''''''''''''''''''

Helper to be called by every ``FormFactory`` which extends from ``AbstractFormFactory`` after
everything has been built to call the "afterBuildingFinished" hook on all form elements.

Signature::

   protected function triggerFormBuildingFinished(FormDefinition $form);


.. _apireference-frontendrendering-programmatically-apimethods-formfactoryinterface:

TYPO3\\CMS\\Form\\Domain\\Factory\\FormFactoryInterface
+++++++++++++++++++++++++++++++++++++++++++++++++++++++

.. _apireference-frontendrendering-programmatically-apimethods-formfactoryinterface-build:

build()
'''''''

Build a form definition, depending on some configuration.

Signature::

   public function build(array $configuration, string $prototypeName = null): FormDefinition;


.. _apireference-frontendrendering-programmatically-apimethods-rendererinterface:

TYPO3\\CMS\\Form\\Domain\\Renderer\\RendererInterface
+++++++++++++++++++++++++++++++++++++++++++++++++++++

.. _apireference-frontendrendering-programmatically-apimethods-rendererinterface-setcontrollercontext:

setControllerContext()
''''''''''''''''''''''

Set the controller context which should be used::

   public function setControllerContext(ControllerContext $controllerContext);


.. _apireference-frontendrendering-programmatically-apimethods-rendererinterface-render:

render()
''''''''

Renders the FormDefinition. This method is expected to call the ``beforeRendering`` hook on each form element::

   public function render(): string;


.. _apireference-frontendrendering-programmatically-apimethods-rendererinterface-setformruntime:

setFormRuntime()
''''''''''''''''

Set the current ``FormRuntime``::

   public function setFormRuntime(FormRuntime $formRuntime);


.. _apireference-frontendrendering-programmatically-apimethods-rendererinterface-getformruntime:

getFormRuntime()
''''''''''''''''

Get the current ``FormRuntime``::

   public function getFormRuntime(): FormRuntime;


.. _apireference-frontendrendering-runtimemanipulation:

Runtime manipulation
--------------------

.. _apireference-frontendrendering-runtimemanipulation-hooks:

Hooks
^^^^^


.. _apireference-frontendrendering-runtimemanipulation-hooks-initializeformelement:

initializeFormElement
+++++++++++++++++++++

You can connect to this hook and initialize a form element without defining a
custom implementaion to access the element's ``initializeFormElement`` method.
You only need a class which connects to this hook. Then detect the form
element you wish to initialize. For example, you can use this hook to prefill
form element data from database tables. Note that this hook will be called
**after** all properties from the prototype configuration are set in the form
element but **before** the properties from the form definition are set in the
form element. If you want to prefill form element data after the complete
form element is configured you should use the
:ref:`afterBuildingFinished<apireference-frontendrendering-runtimemanipulation-hooks-afterbuildingfinished>` hook.

The initializeFormElement hook is invoked by the methods ``TYPO3\CMS\Form\Domain\Model\FormElements\Page::createElement()``
and ``TYPO3\CMS\Form\Domain\Model\FormElements\Section::createElement()``.
That means the hook will **not** be triggered for ``Pages``. At this point
you do not have access to submitted form element values.


.. _apireference-frontendrendering-runtimemanipulation-hooks-initializeformelement-connect:

Connect to the hook
'''''''''''''''''''

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-frontendrendering-runtimemanipulation-hooks-initializeformelement-use:

Use the hook
''''''''''''

::

   /**
    * @param \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable
    * @return void
    */
   public function initializeFormElement(\TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable)
   {
       if ($renderable->getUniqueIdentifier() === 'contactForm-text-1') {
           $renderable->setDefaultValue('foo');
       }
   }


.. _useATimestampAsKeyPlease:

What does <useATimestampAsKeyPlease> mean?
++++++++++++++++++++++++++++++++++++++++++

Timestamps are recommended for hooks such as those of the form framework, as
seen in the following example::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


Leaving the section ``<useATimestampAsKeyPlease>`` as is is not recommended.
It does nothing except cause the extension to fail and an error message to be
delivered. Nor should it be replaced with a function like time(), as the key
should be unalterable. Instead, replace this section with the current UNIX
timestamp the moment you are implementing the hook. Check out the following
example::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement'][1507018413]
       = \VENDOR\YourNamespace\YourClass::class;


The purpose of timestamps is to prevent conflicts that arise when two or more
extensions within one TYPO3 installation use identical keys (e.g.
``$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement']['foo'])``.
When timestamps are used, even a one-second difference in the time different
hooks were connected ensures that one hook does not override the other.


.. _apireference-frontendrendering-runtimemanipulation-hooks-beforeremovefromparentrenderable:

beforeRemoveFromParentRenderable
++++++++++++++++++++++++++++++++

This hook is invoked by the methods ``TYPO3\CMS\Form\Domain\Model\FormDefinition::removePage()``,  ``TYPO3\CMS\Form\Domain\Model\FormElements\Page::removeElement()``
and ``TYPO3\CMS\Form\Domain\Model\FormElements\Section::removeElement()``


.. _apireference-frontendrendering-runtimemanipulation-hooks-beforeremovefromparentrenderable-connect:

Connect to the hook
'''''''''''''''''''

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRemoveFromParentRenderable'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-frontendrendering-runtimemanipulation-hooks-beforeremovefromparentrenderable-use:

Use the hook
''''''''''''

::

   /**
    * @param \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable
    * @return void
    */
   public function beforeRemoveFromParentRenderable(\TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable)
   {
   }


.. _apireference-frontendrendering-runtimemanipulation-hooks-afterbuildingfinished:

afterBuildingFinished
+++++++++++++++++++++

This hook is called for each form element after the class ``TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory``
has built the entire form. This hook is triggered just before the
``FormRuntime`` object is generated. At this point, no run-time information
(e.g. assigned form values) is yet available. It can, for example, be used to
generate new form elements within complex forms. The ``ArrayFormFactory`` is
used by EXT:form via the ``RenderViewHelper`` to render forms using a ``form
definition`` YAML file. Each form factory implementation must deal with the
calling of this hook themselves. EXT:form itself uses this hook to initialize
the property-mapper configuration for ``FileUpload`` elements.

.. _apireference-frontendrendering-runtimemanipulation-hooks-afterbuildingfinished-connect:

Connect to the hook
'''''''''''''''''''

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterBuildingFinished'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-frontendrendering-runtimemanipulation-hooks-afterbuildingfinished-use:

Use the hook
''''''''''''

::

   /**
    * @param \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable
    * @return void
    */
   public function afterBuildingFinished(\TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable)
   {
   }


.. _apireference-frontendrendering-runtimemanipulation-hooks-afterinitializecurrentpage:

afterInitializeCurrentPage
++++++++++++++++++++++++++

EXT:form automatically detects the page that should be shown and allow users
only to jump to the directly following (or previous) pages. This hook enables
you to implement a custom behavior, for example pages that are shown only when
other form elements have specific values.


.. _apireference-frontendrendering-runtimemanipulation-hooks-afterinitializecurrentpage-connect:

Connect to the hook
'''''''''''''''''''

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterInitializeCurrentPage'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-frontendrendering-runtimemanipulation-hooks-afterinitializecurrentpage-use:

Use the hook
''''''''''''

::

   /**
    * @param \TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime
    * @param null|\TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface $currentPage
    * @param null|\TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface $lastPage
    * @param mixed $elementValue submitted value of the element *before post processing*
    * @return null|\TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface
    */
   public function afterInitializeCurrentPage(\TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime, \TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface $currentPage = null, \TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface $lastPage = null, array $requestArguments = []): ?\TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface
   {
       return $currentPage;
   }


.. _apireference-frontendrendering-runtimemanipulation-hooks-aftersubmit:

afterSubmit
+++++++++++

You can use it for example for dynamic validations which depends on other submitted form element values.
This hook is invoked by the ``FormRuntime`` for each form element **before** values are property mapped, validated and pushed within the FormRuntime's ``FormState``.
If the first page is submitted at the first time you cannot access the form element values from the first page by just calling ``$formRuntime['<someOtherFormElementIdentifier>']`` to access
the submitted form element values from the first page. In this case you can access the submitted raw data through ``$requestArguments``.
EXT:form itself uses this hook to dynamically add validation errors for ``AdvancedPassword`` form elements.


.. _apireference-frontendrendering-runtimemanipulation-hooks-aftersubmit-connect:

Connect to the hook
'''''''''''''''''''

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterSubmit'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-frontendrendering-runtimemanipulation-hooks-aftersubmit-use:

Use the hook
''''''''''''

::

   /**
    * @param \TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime
    * @param \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable
    * @param mixed $elementValue submitted value of the element *before post processing*
    * @param array $requestArguments submitted raw request values
    * @return void
    */
   public function afterSubmit(\TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime, \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface $renderable, $elementValue, array $requestArguments = [])
   {
       return $elementValue;
   }


.. _apireference-frontendrendering-runtimemanipulation-hooks-beforerendering:

beforeRendering
+++++++++++++++

This is a hook that is invoked by the rendering system before the corresponding element is rendered.
Use this to access previously submitted values and/or modify the ``FormRuntime`` before an element is outputted to the browser.
This hook is called after all validations and property mappings are done.

.. _apireference-frontendrendering-runtimemanipulation-hooks-beforerendering-connect:

Connect to the hook
'''''''''''''''''''

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRendering'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-frontendrendering-runtimemanipulation-hooks-beforerendering-use:

Use the hook
''''''''''''

::

   /**
    * @param \TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime
    * @param \TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface $renderable
    * @return void
    */
   public function beforeRendering(\TYPO3\CMS\Form\Domain\Runtime\FormRuntime $formRuntime, \TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface $renderable)
   {
   }


.. _apireference-finisheroptions:

Finisher Options
================

.. _apireference-finisheroptions-closurefinisher:

Closure finisher
----------------

This finisher can only be used in programmatically-created forms. It makes it
possible to execute one's own finisher code without having to implement/
declare this finisher.

Usage through code::

   $closureFinisher = $this->objectManager->get(ClosureFinisher::class);
   $closureFinisher->setOption('closure', function($finisherContext) {
       $formRuntime = $finisherContext->getFormRuntime();
       // ...
   });
   $formDefinition->addFinisher($closureFinisher);


.. _apireference-finisheroptions-closurefinisher-options:

Options
^^^^^^^

.. _apireference-finisheroptions-closurefinisher-options-closure:

closure
+++++++

:aspect:`Data type`
      \Closure

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      null


.. _apireference-finisheroptions-confirmationfinisher:

Confirmation finisher
---------------------

A simple finisher that outputs a given text.

Usage within form definition

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: Confirmation
       options:
         message: 'Thx for using TYPO3'
   ...


Usage through code::

   $formDefinition->createFinisher('Confirmation', [
       'message' => 'foo',
   ]);

or create manually (not preferred)::

   $confirmationFinisher = $this->objectManager->get(ConfirmationFinisher::class);
   $confirmationFinisher->setOptions([
       'message' => 'foo',
   ]);
   $formDefinition->addFinisher($confirmationFinisher);


.. _apireference-finisheroptions-confirmationfinisher-options:

Options
^^^^^^^

.. _apireference-finisheroptions-confirmationfinisher-options-message:

message
+++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      The form has been submitted.


.. _apireference-finisheroptions-deleteuploadsfinisher:

DeleteUploads finisher
----------------------

This finisher remove the currently submited files.
Use this finisher e.g after the email finisher if you don't want to keep the files online.


Usage within form definition

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: DeleteUploads
   ...


Usage through code::

   $formDefinition->createFinisher('DeleteUploads');

or create manually (not preferred)::

   $deleteUploadsFinisher = $this->objectManager->get(DeleteUploadsFinisher::class);
   $formDefinition->addFinisher($deleteUploadsFinisher);


.. _apireference-finisheroptions-emailfinisher:

Email finisher
--------------

This finisher sends an email to one recipient.
EXT:form uses 2 EmailFinisher declarations with the identifiers ``EmailToReceiver`` and ``EmailToSender``.

Usage within form definition

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: EmailToReceiver
       options:
         subject: 'Your message'
         recipientAddress: your.company@example.com
         recipientName: 'Your Company name'
         senderAddress: 'form@example.com'
         senderName: 'form submitter'
   ...


Usage through code::

   $formDefinition->createFinisher('EmailToReceiver', [
       'subject' => 'Your message',
       'recipientAddress' => 'your.company@example.com',
       'recipientName' => 'Your Company name',
       'senderAddress' => 'form@example.com',
       'senderName' => 'form submitter',
   ]);

or create manually (not preferred)::

   $emailFinisher = $this->objectManager->get(EmailFinisher::class);
   $emailFinisher->setOptions([
       'subject' => 'Your message',
       'recipientAddress' => 'your.company@example.com',
       'recipientName' => 'Your Company name',
       'senderAddress' => 'form@example.com',
       'senderName' => 'form submitter',
   ]);
   $formDefinition->addFinisher($emailFinisher);


.. _apireference-finisheroptions-emailfinisher-options:

Options
^^^^^^^

.. _apireference-finisheroptions-emailfinisher-options-subject:

subject
+++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Subject of the email


.. _apireference-finisheroptions-emailfinisher-options-recipientaddress:

recipientAddress
++++++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Email address of the recipient (To)


.. _apireference-finisheroptions-emailfinisher-options-recipientname:

recipientName
+++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty string

:aspect:`Description`
      Human-readable name of the recipient


.. _apireference-finisheroptions-emailfinisher-options-senderaddress:

senderAddress
+++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Email address of the sender/ visitor (From)


.. _apireference-finisheroptions-emailfinisher-options-sendername:

senderName
++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty string

:aspect:`Description`
      Human-readable name of the sender


.. _apireference-finisheroptions-emailfinisher-options-replytoaddress:

replyToAddress
++++++++++++++

:aspect:`Data type`
      string/ array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Email address of to be used as reply-to email (use multiple addresses with an array)

.. note::

   For the moment, the ``form editor`` cannot deal with multiple reply-to addresses (use multiple addresses with an array)


.. _apireference-finisheroptions-emailfinisher-options-carboncopyaddress:

carbonCopyAddress
+++++++++++++++++

:aspect:`Data type`
      string/ array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Email address of the copy recipient (use multiple addresses with an array)

.. note::

   For the moment, the ``form editor`` cannot deal with multiple copy recipient addresses (use multiple addresses with an array)


.. _apireference-finisheroptions-emailfinisher-options-blindcarboncopyaddress:

blindCarbonCopyAddress
++++++++++++++++++++++

:aspect:`Data type`
      string/ array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Email address of the blind copy recipient (use multiple addresses with an array)

.. note::

   For the moment, the ``form editor`` cannot deal with multiple blind copy recipient addresses (use multiple addresses with an array)


.. _apireference-finisheroptions-emailfinisher-options-format:

format
++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      html

:aspect:`possible values`
      html/ plaintext

:aspect:`Description`
      The format of the email. By default mails are sent as HTML.


.. _apireference-finisheroptions-emailfinisher-options-attachuploads:

attachUploads
+++++++++++++

:aspect:`Data type`
      bool

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      true

:aspect:`Description`
      If set, all uploaded items are attached to the email.


.. _apireference-finisheroptions-emailfinisher-options-translation-translationfile:

translation.translationFile
+++++++++++++++++++++++++++

:aspect:`Data type`
      string/ array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      If set, this translation file(s) will be used for finisher option translations.
      If not set, the translation file(s) from the 'Form' element will be used.
      Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>` for more informations.


.. _apireference-finisheroptions-emailfinisher-options-translation-language:

translation.language
++++++++++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      If not set, the finisher options are translated depending on the current frontend language (if translations exists).
      This option allows you to force translations for a given sys_language isocode, e.g 'dk' or 'de'.
      Read :ref:`Translate finisher options<concepts-frontendrendering-translation-finishers>` for more informations.


.. _apireference-finisheroptions-emailfinisher-options-templatepathandfilename:

templatePathAndFilename
+++++++++++++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      'EXT:form/Resources/Private/Frontend/Templates/Finishers/Email/{@format}.html'

:aspect:`Description`
      Template path and filename for the mail body.
      The placeholder {\@format} will be replaced with the value from option ``format``
      The template gets the current :php:`FormRuntime` assigned as :code:`form` and
      the :php:`FinisherVariableProvider` assigned as :code:`finisherVariableProvider`.


.. _apireference-finisheroptions-emailfinisher-options-layoutrootpaths:

layoutRootPaths
+++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Fluid layout paths


.. _apireference-finisheroptions-emailfinisher-options-partialrootpaths:

partialRootPaths
++++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Fluid partial paths


.. _apireference-finisheroptions-emailfinisher-options-templaterootpaths:

templateRootPaths
+++++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      Fluid template paths; all templates get the current :php:`FormRuntime`
      assigned as :code:`form` and the :php:`FinisherVariableProvider` assigned
      as :code:`finisherVariableProvider`.


.. _apireference-finisheroptions-emailfinisher-options-variables:

variables
+++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value (for 'EmailToReceiver' and 'EmailToSender' declarations)`
      undefined

:aspect:`Description`
      associative array of variables which are available inside the Fluid template


.. _apireference-finisheroptions-flashmessagefinisher:

FlashMessage finisher
---------------------

A simple finisher that adds a message to the FlashMessageContainer.

Usage within form definition

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: FlashMessage
       options:
         messageBody: 'Thx for using TYPO3'
         messageTitle: 'Merci'
         severity: 0
   ...


Usage through code::

   $formDefinition->createFinisher('FlashMessage', [
       'messageBody' => 'Thx for using TYPO3',
       'messageTitle' => 'Merci',
       'severity' => \TYPO3\CMS\Core\Messaging\AbstractMessage::OK,
   ]);

or create manually (not preferred)::

   $flashMessageFinisher = $this->objectManager->get(FlashMessageFinisher::class);
   $flashMessageFinisher->setOptions([
       'messageBody' => 'Thx for using TYPO3',
       'messageTitle' => 'Merci',
       'severity' => \TYPO3\CMS\Core\Messaging\AbstractMessage::OK,
   ]);
   $formDefinition->addFinisher($flashMessageFinisher);


.. _apireference-finisheroptions-flashmessagefinisher-options:

Options
^^^^^^^

.. _apireference-finisheroptions-flashmessagefinisher-options-messagebody:

messageBody
+++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      null

:aspect:`Description`
      The flash message body


.. _apireference-finisheroptions-flashmessagefinisher-options-messagetitle:

messageTitle
++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty string

:aspect:`Description`
      The flash message title


.. _apireference-finisheroptions-flashmessagefinisher-options-messagearguments:

messageArguments
++++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty array

:aspect:`Description`
      The flash message arguments, if needed


.. _apireference-finisheroptions-flashmessagefinisher-options-messagecode:

messageCode
+++++++++++

:aspect:`Data type`
      int

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      null

:aspect:`Description`
      The flash message code, if needed


.. _apireference-finisheroptions-flashmessagefinisher-options-severity:

severity
++++++++

:aspect:`Data type`
      int

:aspect:`Mandatory`
      No

:aspect:`Default value`
      \TYPO3\CMS\Core\Messaging\AbstractMessage::OK (0)

:aspect:`Description`
      The flash message severity code.
      See \TYPO3\CMS\Core\Messaging\AbstractMessage constants for the codes.


.. _apireference-finisheroptions-redirectfinisher:

Redirect finisher
-----------------

A simple finisher that redirects to another page.

Usage within form definition

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: Redirect
       options:
         pageUid: 1
         additionalParameters: 'param1=value1&param2=value2'
   ...


Usage through code::

   $formDefinition->createFinisher('Redirect', [
       'pageUid' => 1,
       'additionalParameters' => 'param1=value1&param2=value2',
   ]);

or create manually (not preferred)::

   $redirectFinisher = $this->objectManager->get(RedirectFinisher::class);
   $redirectFinisher->setOptions([
       'pageUid' => 1,
       'additionalParameters' => 'param1=value1&param2=value2',
   ]);
   $formDefinition->addFinisher($redirectFinisher);


.. _apireference-finisheroptions-redirectfinisher-options:

Options
^^^^^^^

.. _apireference-finisheroptions-redirectfinisher-options-pageuid:

pageUid
+++++++

:aspect:`Data type`
      int

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      1

:aspect:`Description`
      Redirect to this page uid


.. _apireference-finisheroptions-redirectfinisher-options-additionalparameters:

additionalParameters
++++++++++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty string

:aspect:`Description`
      Additional parameters which should be used on the target page


.. _apireference-finisheroptions-redirectfinisher-options-delay:

delay
+++++

:aspect:`Data type`
      int

:aspect:`Mandatory`
      No

:aspect:`Default value`
      0

:aspect:`Description`
      The redirect delay in seconds.


.. _apireference-finisheroptions-redirectfinisher-options-statuscode:

statusCode
++++++++++

:aspect:`Data type`
      int

:aspect:`Mandatory`
      No

:aspect:`Default value`
      303

:aspect:`Description`
      The HTTP status code for the redirect. Default is "303 See Other".


.. _apireference-finisheroptions-savetodatabasefinisher:

SaveToDatabase finisher
-----------------------

This finisher saves the data from a submitted form into a database table.


Usage within form definition

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: SaveToDatabase
       options:
         table: 'fe_users'
         mode: update
         whereClause:
           uid: 1
         databaseColumnMappings:
           pid:
             value: 1
         elements:
           textfield-identifier-1:
             mapOnDatabaseColumn: 'first_name'
           textfield-identifier-2:
             mapOnDatabaseColumn: 'last_name'
           textfield-identifier-3:
             mapOnDatabaseColumn: 'username'
           advancedpassword-1:
             mapOnDatabaseColumn: 'password'
             skipIfValueIsEmpty: true
   ...


Usage through code::

   $formDefinition->createFinisher('SaveToDatabase', [
       'table' => 'fe_users',
       'mode' => 'update',
       'whereClause' => [
           'uid' => 1,
       ],
       'databaseColumnMappings' => [
           'pid' => ['value' => 1],
       ],
       'elements' => [
           'textfield-identifier-1' => ['mapOnDatabaseColumn' => 'first_name'],
           'textfield-identifier-2' => ['mapOnDatabaseColumn' => 'last_name'],
           'textfield-identifier-3' => ['mapOnDatabaseColumn' => 'username'],
           'advancedpassword-1' => [
               'mapOnDatabaseColumn' => 'password',
               'skipIfValueIsEmpty' => true,
           ],
       ],
   ]);

or create manually (not preferred)::

   $saveToDatabaseFinisher = $this->objectManager->get(SaveToDatabaseFinisher::class);
   $saveToDatabaseFinisher->setOptions([
       'table' => 'fe_users',
       'mode' => 'update',
       'whereClause' => [
           'uid' => 1,
       ],
       'databaseColumnMappings' => [
           'pid' => ['value' => 1],
       ],
       'elements' => [
           'textfield-identifier-1' => ['mapOnDatabaseColumn' => 'first_name'],
           'textfield-identifier-2' => ['mapOnDatabaseColumn' => 'last_name'],
           'textfield-identifier-3' => ['mapOnDatabaseColumn' => 'username'],
           'advancedpassword-1' => [
               'mapOnDatabaseColumn' => 'password',
               'skipIfValueIsEmpty' => true,
           ],
       ],
   ]);
   $formDefinition->addFinisher($saveToDatabaseFinisher);

You can write options as an array to perform multiple database operations.

Usage within form definition

.. code-block:: yaml

   identifier: example-form
   label: 'example'
   type: Form

   finishers:
     -
       identifier: SaveToDatabase
       options:
         1:
           table: 'my_table'
           mode: insert
           databaseColumnMappings:
             some_column:
               value: 'cool'
         2:
           table: 'my_other_table'
           mode: update
           whereClause:
             pid: 1
           databaseColumnMappings:
             some_other_column:
               value: '{SaveToDatabase.insertedUids.1}'
   ...


Usage through code::

   $formDefinition->createFinisher('SaveToDatabase', [
       1 => [
           'table' => 'my_table',
           'mode' => 'insert',
           'databaseColumnMappings' => [
               'some_column' => ['value' => 'cool'],
           ],
       ],
       2 => [
           'table' => 'my_other_table',
           'mode' => 'update',
           'whereClause' => [
               'pid' => 1,
           ],
           'databaseColumnMappings' => [
               'some_other_column' => ['value' => '{SaveToDatabase.insertedUids.1}'],
           ],
       ],
   ]);

or create manually (not preferred)::

   $saveToDatabaseFinisher = $this->objectManager->get(SaveToDatabaseFinisher::class);
   $saveToDatabaseFinisher->setOptions([
       1 => [
           'table' => 'my_table',
           'mode' => 'insert',
           'databaseColumnMappings' => [
               'some_column' => ['value' => 'cool'],
           ],
       ],
       2 => [
           'table' => 'my_other_table',
           'mode' => 'update',
           'whereClause' => [
               'pid' => 1,
           ],
           'databaseColumnMappings' => [
               'some_other_column' => ['value' => '{SaveToDatabase.insertedUids.1}'],
           ],
       ],
   ]);
   $formDefinition->addFinisher($saveToDatabaseFinisher);


This performs 2 database operations.
One insert and one update.
You can access the inserted uids through '{SaveToDatabase.insertedUids.<theArrayKeyNumberWithinOptions>}'
If you perform a insert operation, the value of the inserted database row will be stored within the FinisherVariableProvider.
<theArrayKeyNumberWithinOptions> references to the numeric options.* key.


.. _apireference-finisheroptions-savetodatabasefinisher-options:

Options
^^^^^^^

.. _apireference-finisheroptions-savetodatabasefinisher-options-table:

table
+++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      null

:aspect:`Description`
      Insert or update values into this table.


.. _apireference-finisheroptions-savetodatabasefinisher-options-mode:

mode
++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value`
      'insert'

:aspect:`Possible values`
      insert/ update

:aspect:`Description`
      ``insert`` will create a new database row with the values from the submitted form and/or some predefined values. @see options.elements and options.databaseFieldMappings

      ``update`` will update a given database row with the values from the submitted form and/or some predefined values. 'options.whereClause' is then required.


.. _apireference-finisheroptions-savetodatabasefinisher-options-whereclause:

whereClause
+++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      Yes, if mode = update

:aspect:`Default value`
      empty array

:aspect:`Description`
      This where clause will be used for a database update action


.. _apireference-finisheroptions-savetodatabasefinisher-options-elements:

elements
++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      empty array

:aspect:`Description`
      Use ``options.elements`` to map form element values to existing database columns.
      Each key within ``options.elements`` has to match with a form element identifier.
      The value for each key within ``options.elements`` is an array with additional informations.


.. _apireference-finisheroptions-savetodatabasefinisher-options-elements-<formelementidentifier>-mapondatabasecolumn:

elements.<formElementIdentifier>.mapOnDatabaseColumn
++++++++++++++++++++++++++++++++++++++++++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      undefined

:aspect:`Description`
      The value from the submitted form element with the identifier ``<formElementIdentifier>`` will be written into this database column.


.. _apireference-finisheroptions-savetodatabasefinisher-options-elements-<formelementidentifier>-skipifvalueisempty:

elements.<formElementIdentifier>.skipIfValueIsEmpty
+++++++++++++++++++++++++++++++++++++++++++++++++++

:aspect:`Data type`
      bool

:aspect:`Mandatory`
      No

:aspect:`Default value`
      false

:aspect:`Description`
      Set this to true if the database column should not be written if the value from the submitted form element with the identifier
      ``<formElementIdentifier>`` is empty (think about password fields etc.). Empty means strings without content, whitespace is valid content.


.. _apireference-finisheroptions-savetodatabasefinisher-options-elements-<formelementidentifier>-savefileidentifierinsteadofuid:

elements.<formElementIdentifier>.saveFileIdentifierInsteadOfUid
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

:aspect:`Data type`
      bool

:aspect:`Mandatory`
      No

:aspect:`Default value`
      false

:aspect:`Description`
      Set this to true if the database column should not be written if the value from the submitted form element with the identifier
      ``<formElementIdentifier>`` is empty (think about password fields etc.).

      This setting only rules for form elements which creates a FAL object like ``FileUpload`` or ``ImageUpload``.
      By default, the uid of the FAL object will be written into the database column. Set this to true if you want to store the
      FAL identifier (1:/user_uploads/some_uploaded_pic.jpg) instead.


.. _apireference-finisheroptions-savetodatabasefinisher-options-elements-<formelementidentifier>-dateformat:

elements.<formElementIdentifier>.dateFormat
+++++++++++++++++++++++++++++++++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      No

:aspect:`Default value`
      'U'

:aspect:`Description`
      If the internal datatype is :php:`\DateTime` which is true for the form element types
      :yaml:`DatePicker` and :yaml:`Date`, the object needs to be converted into a string value.
      This option allows you to define the format of the date in case of such a conversion.
      You can use every format accepted by the PHP :php:`date()` function (http://php.net/manual/en/function.date.php#refsect1-function.date-parameters).
      The default value is "U" which leads to a Unix timestamp.


.. _apireference-finisheroptions-savetodatabasefinisher-options-databasecolumnmappings:

databaseColumnMappings
++++++++++++++++++++++

:aspect:`Data type`
      array

:aspect:`Mandatory`
      No

:aspect:`Default value`
      empty array

:aspect:`Description`
      Use this to map database columns to static values.
      Each key within ``options.databaseColumnMappings`` has to match with an existing database column.
      The value for each key within ``options.databaseColumnMappings`` is an array with additional informations.

      This mapping is done *before* the ``options.element`` mapping.
      This means if you map a database column to a value through ``options.databaseColumnMappings`` and map a submitted
      form element value to the same database column through ``options.element``, the submitted form element value
      will override the value you set within ``options.databaseColumnMappings``.


.. _apireference-finisheroptions-savetodatabasefinisher-options-databasecolumnmappings.<databasecolumnname>.value:

databaseColumnMappings.<databaseColumnName>.value
+++++++++++++++++++++++++++++++++++++++++++++++++

:aspect:`Data type`
      string

:aspect:`Mandatory`
      Yes

:aspect:`Default value`
      undefined

:aspect:`Description`
      The value which will be written to the database column.
      You can also use the :ref:`FormRuntime accessor feature<concepts-finishers-customfinisherimplementations-accessingoptions-formruntimeaccessor>` to access every getable property from the ``FormRuntime``
      In short: use something like ``{<formElementIdentifier>}`` to get the value from the submitted form element with the identifier ``<formElementIdentifier>``.

      If you use the FormRuntime accessor feature within ``options.databaseColumnMappings``, the functionality is nearly identical
      to the ``options.elements`` configuration variant.


.. _apireference-finisheroptions-savetodatabasefinisher-options-databasecolumnmappings.<databasecolumnname>.skipifvalueisempty:

databaseColumnMappings.<databaseColumnName>.skipIfValueIsEmpty
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

:aspect:`Data type`
      bool

:aspect:`Mandatory`
      No

:aspect:`Default value`
      false

:aspect:`Description`
      Set this to true if the database column should not be written if the value from ``options.databaseColumnMappings.<databaseColumnName>.value`` is empty.



.. _apireference-formeditor:

Form editor
===========


.. _apireference-formeditor-hooks:

Hooks
-----

EXT:form implements various hooks so that forms can be manipulated while being
created or saved.


.. _apireference-formeditor-hooks-beforeformcreate:

beforeFormCreate
^^^^^^^^^^^^^^^^

The form manager calls the 'beforeFormCreate' hook.


.. _apireference-formeditor-hooks-beforeformcreate-connect:

Connect to the hook
+++++++++++++++++++

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormCreate'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-formeditor-hooks-beforeformcreate-use:

Use the hook
++++++++++++

::

   /**
    * @param string $formPersistenceIdentifier
    * @param array $formDefinition
    * @return array
    */
   public function beforeFormCreate(string $formPersistenceIdentifier, array $formDefinition): array
   {
       return $formDefinition;
   }


.. _apireference-formeditor-hooks-beforeformduplicate:

beforeFormDuplicate
^^^^^^^^^^^^^^^^^^^

The form manager call the 'beforeFormDuplicate' hook.


.. _apireference-formeditor-hooks-beforeformduplicate-connect:

Connect to the hook
+++++++++++++++++++

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDuplicate'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-formeditor-hooks-beforeformduplicate-use:

Use the hook
++++++++++++

::

   /**
    * @param string $formPersistenceIdentifier
    * @param array $formDefinition
    * @return array
    */
   public function beforeFormDuplicate(string $formPersistenceIdentifier, array $formDefinition): array
   {
       return $formDefinition;
   }


.. _apireference-formeditor-hooks-beforeformdelete:

beforeFormDelete
^^^^^^^^^^^^^^^^

The form manager call the 'beforeFormDelete' hook.


.. _apireference-formeditor-hooks-beforeformdelete-connect:

Connect to the hook
+++++++++++++++++++

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDelete'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-formeditor-hooks-beforeformdelete-use:

Use the hook
++++++++++++

::

   /**
    * @param string $formPersistenceIdentifier
    * @return void
    */
   public function beforeFormDelete(string $formPersistenceIdentifier)
   {
   }


.. _apireference-formeditor-hooks-beforeformsave:

beforeFormSave
^^^^^^^^^^^^^^

The form editor call the 'beforeFormSave' hook.


.. _apireference-formeditor-hooks-beforeformsave-connect:

Connect to the hook
+++++++++++++++++++

::

   $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormSave'][<useATimestampAsKeyPlease>]
       = \VENDOR\YourNamespace\YourClass::class;


.. note::

   Wondering what :ref:`useATimestampAsKeyPlease<useATimestampAsKeyPlease>`
   means?


.. _apireference-formeditor-hooks-beforeformsave-use:

Use the hook
++++++++++++

::

   /**
    * @param string $formPersistenceIdentifier
    * @param array $formDefinition
    * @return array
    */
   public function beforeFormSave(string $formPersistenceIdentifier, array $formDefinition): array
   {
       return $formDefinition;
   }



.. _apireference-formeditor-stage:

Stage
-----


.. _apireference-formeditor-stage-commonabstractformelementtemplates:

Common abstract view form element templates
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

The basic idea of the ``abstract view`` is to give a quick overview of the
configuration of form elements, without having to click them in order to view
the detailed configuration in the ``Inspector``. The ``form editor`` requires
for each form element an inline HTML template and the corresponding JavaScript
code. Information matching inline HTML templates to the appropriate form
elements must be configured within :ref:`TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formeditor.formEditorPartials <typo3.cms.form.prototypes.\<prototypeidentifier>.formeditor.formeditorpartials>`.
At this point, the key identifying the form element follows a convention:
``FormElement-<formElementTypeIdentifier>``. The value for the key tells the
``form editor`` which inline HTML template should be loaded for the respective
form element. This template is then cloned via JavaScript, brought to life
using the form element configuration and shown in the ``Stage`` component.

You can read about how particular form elements are mapped to inline HTML
templates and how the corresponding JavaScript code are executed :ref:`here <apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-template-perform>`.

The form element inline HTML templates and the corresponding JavaScript code
are configured for reuse. In this way, most form elements you create should be
able to access the components delivered in EXT:form, without requiring separate
implementations (at least we hope so). For your own implementations, study
EXT:form stage templates, which is found under ``Resources/Private/Backend/Partials/FormEditor/Stage/*``.
The corresponding JavaScript code is found under ``Resources/Public/JavaScript/Backend/FormEditor/StageComponent.js``.
The method ``_renderTemplateDispatcher()`` shows, which methods will be used to
render the respective form elements.

Essentially, two different inline HTML templates exists that can be rendered
with two different JavaScript methods, which are described below. The other
inline HTML templates are almost all versions of these two basic variants and
show extra/ other form-element information. The same applies to the
corresponding JavaScript codes.


.. _apireference-formeditor-stage-commonabstractformelementtemplates-simpletemplate:

Stage/SimpleTemplate
++++++++++++++++++++

This template displays the ``label`` property of the form element. Depending on
the JavaScript rendering method used, a validator icon will be shown on the
right as soon as a validator is added to the form element. In this case, the
used validator labels are likewise displayed, if the form element is selected
and/ or the cursor hovers over the form element. This template should generally
be enough for all possible, self-defined form elements.

The ``Stage/SimpleTemplate`` can then :ref:`be rendered <apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-template-perform>`
with the method ``getFormEditorApp().getViewModel().getStage().renderSimpleTemplateWithValidators()``.


.. _apireference-formeditor-stage-commonabstractformelementtemplates-selecttemplate:

Stage/SelectTemplate
++++++++++++++++++++

This template behaves like the ``Stage/SimpleTemplate`` except that it also
shows the chosen options labels of the form elements. This is naturally only
possible for form elements that have ``properties.options.*`` values, e.g.
``MultiCheckbox``:

.. code-block:: yaml

       type: MultiCheckbox
       identifier: multicheckbox-1
       label: 'Multi checkbox'
       properties:
         options:
           value1: label1
           value2: label2

The template will now list 'label1' and 'label2'.

You can copy this template variant for your own form element, if that form-
element template also lists array values, which, however, are not found under
``properties.options.*``. For this purpose, the 'Stage/FileUploadTemplate' is
an example. It is basically the 'Stage/SelectTemplate' template, with one
altered property.

In the ``FileUpload`` form element, multiple property values are available
under ``properties.allowedMimeTypes.*`` as an array.

.. code-block:: yaml

       type: FileUpload
       identifier: fileupload-1
       label: 'File upload'
       properties:
         saveToFileMount: '1:/user_upload/'
         allowedMimeTypes:
           - application/msexcel
           - application/pdf

Stage/SelectTemplate

.. code-block:: html

   <div data-identifier="multiValueContainer" data-template-property="properties.options">

Stage/FileUploadTemplate

.. code-block:: html

   <div data-identifier="multiValueContainer" data-template-property="properties.allowedMimeTypes">

``data-template-property`` contains the path to the property, which is to be
read out of the form element and then shown in the template.

The ``Stage/SelectTemplate`` can then :ref:`be rendered <apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-template-perform>`
with the method ``getFormEditorApp().getViewModel().getStage().renderSelectTemplates()``.


.. _apireference-formeditor-basicjavascriptconcepts:

Basic JavaScript Concepts
-------------------------


.. _apireference-formeditor-basicjavascriptconcepts-events:

Events
^^^^^^

EXT:form implements the ``publish/subscribe pattern`` to put the event handling
into effect. To learn more about this pattern, you should read
https://addyosmani.com/resources/essentialjsdesignpatterns/book/.
Note that the order of the subscriber is not manipulable and that information
flow between the subscribers does not exist. All events must be asynchronously
designed.

Publish an event:

.. code-block:: javascript

   getPublisherSubscriber().publish('eventname', [argumentToPublish1, argumentToPublish2, ...]);

Subscribe to an event:

.. code-block:: javascript

   var subscriberToken = getPublisherSubscriber().subscribe('eventname', function(topic, args) {
       // args[0] = argumentToPublish1
       // args[1] = argumentToPublish2
       // ...
   });

Unsubscribe an event subscriber:

.. code-block:: javascript

   getPublisherSubscriber().unsubscribe(subscriberToken);

EXT:form itself publishes and subscribes to the following events:


.. _apireference-formeditor-basicjavascriptconcepts-events-ajax-beforesend:

ajax/beforeSend
+++++++++++++++

Each Ajax request is called before this event is sent. EXT:form uses this event
to display the spinner icon on the save button.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('ajax/beforeSend', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-ajax-complete:

ajax/complete
+++++++++++++

Each Ajax request is called after the end of this event. EXT:form uses this
event to remove the spinner icon on the save button.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('ajax/complete', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-core-ajax-error:

core/ajax/error
+++++++++++++++

This event is called if the Ajax request, which is used to save the form or to
render the current page of the form in the ``preview view``, fails. EXT:form
uses this event to show an error message as a flash message and to show the
received error text in the ``preview view``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = jqXHR
    *              args[1] = textStatus
    *              args[2] = errorThrown
    * @return void
    */
   getPublisherSubscriber().subscribe('core/ajax/error', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-core-ajax-renderformdefinitionpage-success:

core/ajax/renderFormDefinitionPage/success
++++++++++++++++++++++++++++++++++++++++++

This event is called if the Ajax request that is used to render the current
page of the form in the ``preview view`` was successful. EXT:form uses this
event to display the rendered form in the ``preview view``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = html
    *              args[1] = pageIndex
    * @return void
    */
   getPublisherSubscriber().subscribe('core/ajax/renderFormDefinitionPage/success', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-core-ajax-saveformdefinition-success:

core/ajax/saveFormDefinition/success
++++++++++++++++++++++++++++++++++++

This event is called if the Ajax request that is used to save the form was
successful. EXT:form uses this event to display a success message as a flash
message. The ``form editor`` is also informed that no unsaved content currently
exists.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = html
    * @return void
    */
   getPublisherSubscriber().subscribe('core/ajax/saveFormDefinition/success', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-core-applicationstate-add:

core/applicationState/add
+++++++++++++++++++++++++

The addition/ deletion and movement of form elements und property collection
elements (validators/ finishers) is saved in an internal stack so that the
undo/ redo function can be implemented. This event is called whenever the
current state is added to the stack. EXT:form uses this event to reset the
enabled/ disabled state of the undo/ redo buttons.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = applicationState
    *              args[1] = stackPointer
    *              args[2] = stackSize
    * @return void
    */
   getPublisherSubscriber().subscribe('core/applicationState/add', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-core-currentlyselectedformelementchanged:

core/currentlySelectedFormElementChanged
++++++++++++++++++++++++++++++++++++++++

The method ``getFormEditorApp().setCurrentlySelectedFormElement()`` tells the
``form editor`` which form element should currently be dealt with. This method
calls this event at the end.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElement
    * @return void
    */
   getPublisherSubscriber().subscribe('core/currentlySelectedFormElementChanged', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-core-formelement-somepropertychanged:

core/formElement/somePropertyChanged
++++++++++++++++++++++++++++++++++++

Each :ref:`FormElement model<apireference-formeditor-basicjavascriptconcepts-formelementmodel>`
can write properties into the ``FormElement model`` through the methods ``get``
and ``set``. Each property path can register an event name for the publisher
through the method ``on``. This event is then always called when a property
path is written via ``set``. Read :ref:`FormElement model<concepts-formeditor-basicjavascriptconcepts-formelementmodel>`
for more information. EXT:form automatically registers for all known property
paths of a form element the event ``core/formElement/somePropertyChanged``.
This means that every property written via ``set`` calls this event. Among
other things, EXT:form uses this event for, for example, updating the label of
a form element in other components (e.g. ``Tree`` component ) when this label
is changed. Furthermore, any validation errors from form element properties
are indicated by this event in the ``Tree`` component.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = propertyPath
    *              args[1] = value
    *              args[2] = oldValue
    *              args[3] = formElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('core/formElement/somePropertyChanged', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-collectionelement-moved:

view/collectionElement/moved
++++++++++++++++++++++++++++

The method ``getFormEditorApp().getViewModel().movePropertyCollectionElement()``
calls this event at the end. EXT:form uses this event to re-render the
``Inspector`` component as soon as a property collection element (validator/
finisher) is moved.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = movedCollectionElementIdentifier
    *              args[1] = previousCollectionElementIdentifier
    *              args[2] = nextCollectionElementIdentifier
    *              args[3] = collectionName
    * @return void
    */
   getPublisherSubscriber().subscribe('view/collectionElement/moved', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-collectionelement-new-added:

view/collectionElement/new/added
++++++++++++++++++++++++++++++++

The method ``getFormEditorApp().getViewModel().createAndAddPropertyCollectionElement()``
calls this event at the end. EXT:form uses this event to re-render the
``Inspector`` component as soon as a property collection element (validator/
finisher) is created and added.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = collectionElementIdentifier
    *              args[1] = collectionName
    *              args[2] = formElement
    *              args[3] = collectionElementConfiguration
    *              args[4] = referenceCollectionElementIdentifier
    * @return void
    */
   getPublisherSubscriber().subscribe('view/collectionElement/new/added', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-collectionelement-removed:

view/collectionElement/removed
++++++++++++++++++++++++++++++

The method ``getFormEditorApp().getViewModel().removePropertyCollectionElement()``
calls this event at the end. EXT:form uses this event to re-render the
``Inspector`` component as soon as a property collection element (validator/
finisher) is removed.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = collectionElementIdentifier
    *              args[1] = collectionName
    *              args[2] = formElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/collectionElement/removed', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-formelement-inserted:

view/formElement/inserted
+++++++++++++++++++++++++

The method ``getFormEditorApp().getViewModel().createAndAddFormElement()`` and
the event :ref:`view/insertElements/perform/after<apireference-formeditor-basicjavascriptconcepts-events-view-insertelements-perform-after>`
call this event at the end. EXT:form uses this event to set the current
to-be-processed form element (``getFormEditorApp().setCurrentlySelectedFormElement()``)
and to re-render the ``Tree``, ``Stage`` and ``Inspector`` components.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = newFormElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/formElement/inserted', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-formelement-moved:

view/formElement/moved
++++++++++++++++++++++

The method ``getFormEditorApp().getViewModel().moveFormElement()`` calls this
event at the end.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = movedFormElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/formElement/moved', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-formelement-removed:

view/formElement/removed
++++++++++++++++++++++++

The method ``getFormEditorApp().getViewModel().removeFormElement()`` calls this
event at the end. EXT:form uses this event to set the current to-be-processed
form element (``getFormEditorApp().setCurrentlySelectedFormElement()``) and to
re-render the ``Tree``, ``Stage`` and ``Inspector`` components.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = parentFormElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/formElement/removed', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-header-button-close-clicked:

view/header/button/close/clicked
++++++++++++++++++++++++++++++++

The onClick event of the "Close" button in the ``form editor's`` header section
calls this event. EXT:form uses this event to display a warning message in case
there are unsaved changes.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/header/button/close/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-header-button-newpage-clicked:

view/header/button/newPage/clicked
++++++++++++++++++++++++++++++++++

The onClick event of the "new page" button in the ``form editor's`` header
section calls this event. EXT:form uses this event to display the "new page"
dialog box.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = targetEvent
    * @return void
    */
   getPublisherSubscriber().subscribe('view/header/button/newPage/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-header-button-save-clicked:

view/header/button/save/clicked
+++++++++++++++++++++++++++++++

The onClick event of the "save" button in the ``form editor's`` header section
calls this event. EXT:form uses this event either to display a dialog box with
the element in question (if there are validation errors) or to save the ``form
definition`` (if there are no validation errors).

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/header/button/save/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-header-formsettings-clicked:

view/header/formSettings/clicked
++++++++++++++++++++++++++++++++

The onClick event of the "settings"  button in the ``form editor's`` header
section calls this event. EXT:form uses this event to select the root form
element.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/header/formSettings/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-insertelements-perform-after:

view/insertElements/perform/after
+++++++++++++++++++++++++++++++++

This event is called from the "new element" dialog box upon selection of a form
element:

- if "After" in the "Create new element" split button in the form-element toolbar for composite elements (e.g. fieldset) is clicked.
- if the "Create new element" button in the form-element toolbar for non-composite elements is clicked.

EXT:form uses this event to create a new form element (``getFormEditorApp().getViewModel().createAndAddFormElement()``)
and then move (``getFormEditorApp().getViewModel().moveFormElement()``) it
below the currently selected element (sibling). At the end of this event, the
event :ref:`view/formElement/inserted<apireference-formeditor-basicjavascriptconcepts-events-view-formelement-inserted>`
is called. The event ``view/formElement/inserted`` in ``getFormEditorApp().getViewModel().createAndAddFormElement()``
was previously deactivated.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementType
    * @return void
    */
   getPublisherSubscriber().subscribe('view/insertElements/perform/after', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-insertelements-perform-bottom:

view/insertElements/perform/bottom
++++++++++++++++++++++++++++++++++

This event is called from the "new element" dialog box upon selection of a form
element:

- if, in the ``abstract view`` mode, the "Create new element" button at the end of the ``Stage`` component is clicked.

EXT:form uses this event to create a new form element (``getFormEditorApp().getViewModel().createAndAddFormElement()``).
This element is always created as the last element of the currently selected
page.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementType
    * @return void
    */
   getPublisherSubscriber().subscribe('view/insertElements/perform/bottom', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-insertelements-perform-inside:

view/insertElements/perform/inside
++++++++++++++++++++++++++++++++++

This event is called from the "new element" dialog box upon selection of a form
element:

- if "Inside" in the "Create new element" split button in the form-element toolbar for composite elements (e.g. fieldset) is clicked.

EXT:form uses this event to create a new form element as a child element of the
currently selected element (``getFormEditorApp().getViewModel().createAndAddFormElement()``).

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementType
    * @return void
    */
   getPublisherSubscriber().subscribe('view/insertElements/perform/inside', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-insertpages-perform:

view/insertPages/perform
++++++++++++++++++++++++

This event is called from the "new element" dialog box upon selection of a page
element:

- if the "Create new page" icon in the header section is clicked.
- if the "Create new page" button in the ``Tree`` component is clicked.

EXT:form uses this event to create a new page after the currently selected page
(``getFormEditorApp().getViewModel().createAndAddFormElement()``).

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementType
    * @return void
    */
   getPublisherSubscriber().subscribe('view/insertPages/perform', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-collectionelement-existing-selected:

view/inspector/collectionElement/existing/selected
++++++++++++++++++++++++++++++++++++++++++++++++++

The ``inspector editors`` :ref:`ValidatorsEditor <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.finisherseditor>`
and :ref:`FinishersEditor <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.validatorseditor>`
are used to display the available validators/ finishers for a form element as a
select box. Furthermore, these ``inspector editors`` indicate that in the
``form definition``, validators/ finishers for the currently selected element
already exist. This occurs through the event ``view/inspector/collectionElement/existing/selected``.
EXT:form uses this event to render these validators/ finishers and their
tentatively configured ``inspector editors`` (``getFormEditorApp().getViewModel().renderInspectorCollectionElementEditors()``).

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = collectionElementIdentifier
    *              args[1] = collectionName
    * @return void
    */
   getPublisherSubscriber().subscribe('view/inspector/collectionElement/existing/selected', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-collectionelement-new-selected:

view/inspector/collectionElement/new/selected
+++++++++++++++++++++++++++++++++++++++++++++

The ``inspector editors`` :ref:`ValidatorsEditor <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.finisherseditor>`
and :ref:`FinishersEditor <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.validatorseditor>`
are used to display the available validators/ finishers for a form element as a
select box. The onChange event of the select box then calls this event. In
addition, the ``inspector editor`` :ref:`RequiredValidatorEditor <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.requiredvalidatoreditor>`
calls this event when a checkbox is chosen. EXT:form uses this event to add and
render the validator/ finisher of the ``form definition`` via ``getFormEditorApp().getViewModel().createAndAddPropertyCollectionElement()``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = collectionElementIdentifier
    *              args[1] = collectionName
    * @return void
    */
   getPublisherSubscriber().subscribe('view/inspector/collectionElement/new/selected', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-collectionelement-dnd-update:

view/inspector/collectionElements/dnd/update
++++++++++++++++++++++++++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'update' event from 'jquery.mjs.nestedSortable' calls
the ``view/inspector/collectionElements/dnd/update`` event if a property
collection element in the ``Inspector`` component is sorted. EXT:form uses this
event to move the validator/ finisher in the ``form definition`` via the method
``getFormEditorApp().getViewModel().movePropertyCollectionElement()``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = movedCollectionElementIdentifier
    *              args[1] = previousCollectionElementIdentifier
    *              args[2] = nextCollectionElementIdentifier
    *              args[3] = collectionName
    * @return void
    */
   getPublisherSubscriber().subscribe('view/inspector/collectionElements/dnd/update', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-editor-insert-perform:

view/inspector/editor/insert/perform
++++++++++++++++++++++++++++++++++++

The methods ``getFormEditorApp().getViewModel().renderInspectorEditors()`` (to
render all ``inspector editors`` for a form element) and ``getFormEditorApp().getViewModel().renderInspectorCollectionElementEditors()``
(to render the ``inspector editors`` for a validator/ finisher) call this event
at the end. Strictly speaking, the ``Inspector`` component in the method
``_renderEditorDispatcher()`` calls this event.
Each ``inspector editor`` has the property :ref:`templateName <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.templatename>`,
which gives the ``form editor`` two pieces of information. On the one hand the
``templateName`` must match with a key within the :ref:`TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formeditor.formEditorPartials <typo3.cms.form.prototypes.\<prototypeidentifier>.formeditor.formeditorpartials>`.
The ``form editor`` can consequently load a corresponding inline HTML template
for the ``inspector editor``. On the other hand, the ``Inspector`` component
must be told which JavaScript code should be executed for the
``inspector editor``. For the ``inspector editors`` delivered with EXT:form,
this occurs within the method ``_renderEditorDispatcher()``.
An existing hard-coded list of known ``inspector editors`` determines, by means
of the property ``templateName``, which corresponding JavaScript method should
be executed for the ``inspector editor``. At the end, the event
``view/inspector/editor/insert/perform`` is called. If you wish to implement
your own ``inspector editor``, you can use this event to execute in
:ref:`your own JavaScript module <concepts-formeditor-basicjavascriptconcepts-registercustomjavascriptmodules>`.
the corresponding JavaScript code, with the help of the property
``templateName``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = editorConfiguration
    *              args[1] = editorHtml
    *              args[2] = collectionElementIdentifier
    *              args[3] = collectionName
    * @return void
    */
   getPublisherSubscriber().subscribe('view/inspector/editor/insert/perform', function(topic, args) {
   });

A simple example that registers a custom ``inspector editor`` called 'Inspector-MyCustomInspectorEditor' and adds it to text form elements:

.. code-block:: yaml

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formEditor:
               dynamicRequireJsModules:
                 additionalViewModelModules:
                   10: 'TYPO3/CMS/MySitePackage/Backend/FormEditor/ViewModel'
               formEditorFluidConfiguration:
                 partialRootPaths:
                   100: 'EXT:my_site_package/Resources/Private/Backend/Partials/FormEditor/'
               formEditorPartials:
                 Inspector-MyCustomInspectorEditor: 'Inspector/MyCustomInspectorEditor'
             formElementsDefinition:
               Text:
                 formEditor:
                   editors:
                     600:
                       templateName: 'Inspector-MyCustomInspectorEditor'
                       ...

.. code-block:: javascript
   :emphasize-lines: 107-116

   /**
    * Module: TYPO3/CMS/MySitePackage/Backend/FormEditor/ViewModel
    */
   define(['jquery',
           'TYPO3/CMS/Form/Backend/FormEditor/Helper'
           ], function($, Helper) {
           'use strict';

       return (function($, Helper) {

           /**
            * @private
            *
            * @var object
            */
           var _formEditorApp = null;

           /**
            * @private
            *
            * @return object
            */
           function getFormEditorApp() {
               return _formEditorApp;
           };

           /**
            * @private
            *
            * @return object
            */
           function getPublisherSubscriber() {
               return getFormEditorApp().getPublisherSubscriber();
           };

           /**
            * @private
            *
            * @return object
            */
           function getUtility() {
               return getFormEditorApp().getUtility();
           };

           /**
            * @private
            *
            * @param object
            * @return object
            */
           function getHelper() {
               return Helper;
           };

           /**
            * @private
            *
            * @return object
            */
           function getCurrentlySelectedFormElement() {
               return getFormEditorApp().getCurrentlySelectedFormElement();
           };

           /**
            * @private
            *
            * @param mixed test
            * @param string message
            * @param int messageCode
            * @return void
            */
           function assert(test, message, messageCode) {
               return getFormEditorApp().assert(test, message, messageCode);
           };

           /**
            * @private
            *
            * @return void
            * @throws 1491643380
            */
           function _helperSetup() {
               assert('function' === $.type(Helper.bootstrap),
                   'The view model helper does not implement the method "bootstrap"',
                   1491643380
               );
               Helper.bootstrap(getFormEditorApp());
           };

           /**
            * @private
            *
            * @return void
            */
           function _subscribeEvents() {
               /**
                * @private
                *
                * @param string
                * @param array
                *              args[0] = editorConfiguration
                *              args[1] = editorHtml
                *              args[2] = collectionElementIdentifier
                *              args[3] = collectionName
                * @return void
                */
               getPublisherSubscriber().subscribe('view/inspector/editor/insert/perform', function(topic, args) {
                   if (args[0]['templateName'] === 'Inspector-MyCustomInspectorEditor') {
                       renderMyCustomInspectorEditor(
                           args[0],
                           args[1],
                           args[2],
                           args[3]
                       );
                   }
               });
           };

           /**
            * @private
            *
            * @param object editorConfiguration
            * @param object editorHtml
            * @param string collectionElementIdentifier
            * @param string collectionName
            * @return void
            */
           function renderMyCustomInspectorEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
               // do cool stuff
           });

           /**
            * @public
            *
            * @param object formEditorApp
            * @return void
            */
           function bootstrap(formEditorApp) {
               _formEditorApp = formEditorApp;
               _helperSetup();
               _subscribeEvents();
           };

           /**
            * Publish the public methods.
            * Implements the "Revealing Module Pattern".
            */
           return {
               bootstrap: bootstrap
           };
       })($, Helper);
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-inspector-removecollectionelement-perform:

view/inspector/removeCollectionElement/perform
++++++++++++++++++++++++++++++++++++++++++++++

The ``inspector editor`` :ref:`RequiredValidatorEditor <typo3.cms.form.prototypes.\<prototypeidentifier>.formelementsdefinition.\<formelementtypeidentifier>.formeditor.editors.*.requiredvalidatoreditor>`
calls this event, if the checkbox is deselected. EXT:form uses this event to
remove the configured required validator ('NotEmpty') from the ``form
definition`` through the method ``getFormEditorApp().getViewModel().removePropertyCollectionElement()``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = collectionElementIdentifier
    *              args[1] = collectionName
    *              args[2] = formElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/inspector/removeCollectionElement/perform', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-modal-close-perform:

view/modal/close/perform
++++++++++++++++++++++++

If you try to close the ``form editor`` with unsaved content, a dialog box
appears, asking whether you really wish to close it. If you confirm it, this
event is called in the ``check box`` component. EXT:form uses this event to
close the ``form editor`` and return to the ``form manager``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/modal/close/perform', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-modal-removecollectionelement-perform:

view/modal/removeCollectionElement/perform
++++++++++++++++++++++++++++++++++++++++++

If you try to remove a validator/ finisher by clicking the remove icon, a
dialog box appears, asking you to confirm this action. If confirmed, this event
is called in the ``check box`` component. EXT:form uses this event to remove
the validator/ finisher from the ``form definition`` through the method
``getFormEditorApp().getViewModel().removePropertyCollectionElement()``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = collectionElementIdentifier
    *              args[1] = collectionName
    *              args[2] = formElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/modal/removeCollectionElement/perform', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-modal-removeformelement-perform:

view/modal/removeFormElement/perform
++++++++++++++++++++++++++++++++++++

If you try to remove a form element by clicking the remove icon, a dialog box
appears, asking you to confirm this action. If confirmed, this event is called
in the ``check box`` component. EXT:form uses this event to remove the form
element from the ``form definition`` via the method ``getFormEditorApp().getViewModel().removeFormElement()``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/modal/removeFormElement/perform', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-modal-validationerrors-element-clicked:

view/modal/validationErrors/element/clicked
+++++++++++++++++++++++++++++++++++++++++++

If a form element contains a validation error and you try to save the form, a
dialog box appears, listing all form elements with validation errors. One such
form element can be clicked in this dialog box. This event is called by
clicking a form element in the dialog box. EXT:form uses this event to select
and show this form element.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/modal/validationErrors/element/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-paginationnext-clicked:

view/paginationNext/clicked
+++++++++++++++++++++++++++

This event is called if the 'pagination next' button in the ``Stage``
component's header section is clicked. EXT:form uses this event to render the
next page of the form.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/paginationNext/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-paginationprevious-clicked:

view/paginationPrevious/clicked
+++++++++++++++++++++++++++++++

This event is called, if the 'pagination previous' button in the ``Stage``
component's header section is clicked. EXT:form uses this event to render the
previous page of the form.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/paginationPrevious/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-ready:

view/ready
++++++++++

EXT:form makes it possible to load :ref:`your own JavaScript module <concepts-formeditor-basicjavascriptconcepts-registercustomjavascriptmodules>`.
If all modules are loaded, the view-model method ``_loadAdditionalModules``
calls this event. EXT:form uses this event to remove the preloader icon and
finally initialize the ``form editor``.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/ready', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-redobutton-clicked:

view/redoButton/clicked
+++++++++++++++++++++++

This event is called if the redo button in the ``form editor`` header is
clicked. The addition/ deletion and movement of form elements and property
collection elements (validators/ finishers) is saved in an internal stack in
order to reset the undo/ redo functionality. EXT:form uses this event to reset
this stack to the previous state.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/redoButton/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-button-newelement-clicked:

view/stage/abstract/button/newElement/clicked
+++++++++++++++++++++++++++++++++++++++++++++

This event is called if the "Create new element" button at the end of the
``Stage`` component in the ``abstract view`` mode is clicked. EXT:form uses
this event to display the "new element" dialog box.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = targetEvent
    *              args[1] = configuration
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/button/newElement/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-dnd-change:

view/stage/abstract/dnd/change
++++++++++++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'change' event from 'jquery.mjs.nestedSortable' calls
the ``view/stage/abstract/dnd/change`` event in the ``Stage`` component in the
``abstract view`` mode if form elements are sorted. EXT:form uses this event to
set various CSS classes during the drag-and-drop process.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = placeholderDomElement
    *              args[1] = parentFormElementIdentifierPath
    *              args[2] = enclosingCompositeFormElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/dnd/change', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-dnd-start:

view/stage/abstract/dnd/start
+++++++++++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'start' event from 'jquery.mjs.nestedSortable' calls
the ``view/stage/abstract/dnd/start`` event in the ``Stage`` component in the
``abstract view`` mode if form elements are sorted. EXT:form uses this event to
set various CSS classes at the start of the drag-and-drop process.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = draggedFormElementDomElement
    *              args[1] = draggedFormPlaceholderDomElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/dnd/start', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-dnd-stop:

view/stage/abstract/dnd/stop
++++++++++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'stop' event from 'jquery.mjs.nestedSortable' calls the
``view/stage/abstract/dnd/stop`` event in the ``Stage`` component in the
``abstract view`` mode if form elements are sorted. EXT:form uses this event to
to re-render the ``Tree``, ``Stage`` and ``Inspector`` components at the end of
the drag-and-drop process and to select the moved form element.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = draggedFormElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/dnd/stop', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-dnd-update:

view/stage/abstract/dnd/update
++++++++++++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'update' event from 'jquery.mjs.nestedSortable' calls
the ``view/stage/abstract/dnd/update`` event in the ``Stage`` component in the
``abstract view`` mode if form elements are sorted. EXT:form uses this event
to move the form element in the ``form definition`` accordingly at the end of
the drag-and-drop process.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = movedDomElement
    *              args[1] = movedFormElementIdentifierPath
    *              args[2] = previousFormElementIdentifierPath
    *              args[3] = nextFormElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/dnd/update', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-elementtoolbar-button-newelement-clicked:

view/stage/abstract/elementToolbar/button/newElement/clicked
++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

This event is called if the "Create new element" button in the form-element
toolbar or "Inside" or "After" in the split button is clicked. EXT:form uses
this event to display the "New element" dialog box.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = targetEvent
    *              args[1] = configuration
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/elementToolbar/button/newElement/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-postprocess:

view/stage/abstract/render/postProcess
++++++++++++++++++++++++++++++++++++++

This event is called after the ``abstract view`` of the ``Stage`` component has
been rendered. EXT:form uses this event to render the undo/ redo buttons.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/render/postProcess', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-preprocess:

view/stage/abstract/render/preProcess
+++++++++++++++++++++++++++++++++++++

This event is called before the ``abstract view`` of the ``Stage`` component is
rendered.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/render/preProcess', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-abstract-render-template-perform:

view/stage/abstract/render/template/perform
+++++++++++++++++++++++++++++++++++++++++++

The methods ``getFormEditorApp().getViewModel().renderAbstractStageArea()``
call this event. Strictly speaking, the ``Stage`` component in the method
``_renderTemplateDispatcher()`` calls this event. The ``form editor`` requires
for each form element an inline HTML template the corresponding JavaScript
code. Information matching inline HTML templates to the appropriate form
elements must be configured within :ref:`TYPO3.CMS.Form.prototypes.\<prototypeIdentifier>.formeditor.formEditorPartials <typo3.cms.form.prototypes.\<prototypeidentifier>.formeditor.formeditorpartials>`.
At this point, the key identifying the form element follows a convention:
``FormElement-<formElementTypeIdentifier>``. The value for the key tells the
``form editor`` which inline HTML template should be loaded for the respective
form element. The ``_renderTemplateDispatcher()`` method then identifies, by
means of the form element's ``<formElementTypeIdentifier>``, the corresponding
JavaScript code to fill the inline HTML template with life.
``_renderTemplateDispatcher()`` contains a hard-coded list with the
``<formElementTypeIdentifier>`` that is brought in with the EXT:form, and it
renders the inline HTML templates accordingly. At the end, the
``view/stage/abstract/render/template/perform`` event is called. If you wish to
implement your own form element and show it in the ``form editor``, this event
can be used to execute in :ref:`your own JavaScript module <concepts-formeditor-basicjavascriptconcepts-registercustomjavascriptmodules>`
the corresponding JavaScript code, with the help of the ``<formElementTypeIdentifier>``.
This is generally enough to allow the ``Stage/SimpleTemplate`` and/ or
``Stage/SelectTemplate`` inline HTML template to be rendered for your own form
element and, in the JavaScript code, to access the ``getFormEditorApp().getViewModel().getStage().renderSimpleTemplateWithValidators()``
and/ or ``getFormEditorApp().getViewModel().getStage().renderSelectTemplates()``
method delivered with EXT:form. An overview over the functionality of the
formEditorPartials for the ``<formElementTypeIdentifier>`` and its JavaScript
code is found :ref:`here <apireference-formeditor-stage-commonabstractformelementtemplates>`.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElement
    *              args[1] = template
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/abstract/render/template/perform', function(topic, args) {
   });

A simple example reusing the EXT:form inline HTML template ``Stage/SelectTemplate`` and the EXT:form JavaScript code ``renderSelectTemplates()``
for a custom form element with ``<formElementTypeIdentifier>`` = 'GenderSelect'.
In this example, 'GenderSelect' is basically a radio button form element with some predefined options.

.. code-block:: yaml
   :emphasize-lines: 11

   TYPO3:
     CMS:
       Form:
         prototypes:
           standard:
             formEditor:
               dynamicRequireJsModules:
                 additionalViewModelModules:
                   10: 'TYPO3/CMS/MySitePackage/Backend/FormEditor/ViewModel'
               formEditorPartials:
                 FormElement-GenderSelect: 'Stage/SelectTemplate'
             formElementsDefinition:
               GenderSelect:
                 __inheritances:
                   10: 'TYPO3.CMS.Form.prototypes.standard.formElementsDefinition.RadioButton'
                 renderingOptions:
                   templateName: 'RadioButton'
                 properties:
                   options:
                     f: 'Female'
                     m: 'Male'
                     u: 'Unicorn'
                     a: 'Alien'
                 formEditor:
                   label: 'Gender Select'
                   group: select
                   groupSorting: 9000
                   predefinedDefaults:
                     properties:
                       options:
                         f: 'Female'
                         m: 'Male'
                         u: 'Unicorn'
                         a: 'Alien'
                   editors:
                     300: null

.. code-block:: javascript
   :emphasize-lines: 105-109

   /**
    * Module: TYPO3/CMS/MySitePackage/Backend/FormEditor/ViewModel
    */
   define(['jquery',
           'TYPO3/CMS/Form/Backend/FormEditor/Helper'
           ], function($, Helper) {
           'use strict';

       return (function($, Helper) {

           /**
            * @private
            *
            * @var object
            */
           var _formEditorApp = null;

           /**
            * @private
            *
            * @return object
            */
           function getFormEditorApp() {
               return _formEditorApp;
           };

           /**
            * @private
            *
            * @return object
            */
           function getPublisherSubscriber() {
               return getFormEditorApp().getPublisherSubscriber();
           };

           /**
            * @private
            *
            * @return object
            */
           function getUtility() {
               return getFormEditorApp().getUtility();
           };

           /**
            * @private
            *
            * @param object
            * @return object
            */
           function getHelper() {
               return Helper;
           };

           /**
            * @private
            *
            * @return object
            */
           function getCurrentlySelectedFormElement() {
               return getFormEditorApp().getCurrentlySelectedFormElement();
           };

           /**
            * @private
            *
            * @param mixed test
            * @param string message
            * @param int messageCode
            * @return void
            */
           function assert(test, message, messageCode) {
               return getFormEditorApp().assert(test, message, messageCode);
           };

           /**
            * @private
            *
            * @return void
            * @throws 1491643380
            */
           function _helperSetup() {
               assert('function' === $.type(Helper.bootstrap),
                   'The view model helper does not implement the method "bootstrap"',
                   1491643380
               );
               Helper.bootstrap(getFormEditorApp());
           };

           /**
            * @private
            *
            * @return void
            */
           function _subscribeEvents() {
               /**
                * @private
                *
                * @param string
                * @param array
                *              args[0] = formElement
                *              args[1] = template
                * @return void
                */
               getPublisherSubscriber().subscribe('view/stage/abstract/render/template/perform', function(topic, args) {
                   if (args[0].get('type') === 'GenderSelect') {
                       getFormEditorApp().getViewModel().getStage().renderSelectTemplates(args[0], args[1]);
                   }
               });
           };

           /**
            * @public
            *
            * @param object formEditorApp
            * @return void
            */
           function bootstrap(formEditorApp) {
               _formEditorApp = formEditorApp;
               _helperSetup();
               _subscribeEvents();
           };

           /**
            * Publish the public methods.
            * Implements the "Revealing Module Pattern".
            */
           return {
               bootstrap: bootstrap
           };
       })($, Helper);
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-element-clicked:

view/stage/element/clicked
++++++++++++++++++++++++++

This event is called from the ``Stage`` component when a form element is
clicked. EXT:form uses this event to select this element and to display the
form-element toolbar. In addition, the ``Tree`` and ``Inspector`` components
are re-rendered.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/element/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-panel-clicked:

view/stage/panel/clicked
++++++++++++++++++++++++

This event is called if the header section of the ``Stage`` component is
clicked.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/panel/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-stage-preview-render-postprocess:

view/stage/preview/render/postProcess
+++++++++++++++++++++++++++++++++++++

This event is called after the ``preview view`` of the ``Stage`` component has
been rendered. EXT:form uses this event to render the undo/ redo buttons.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/stage/preview/render/postProcess', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-structure-button-newpage-clicked:

view/structure/button/newPage/clicked
+++++++++++++++++++++++++++++++++++++

This event is called from the onClick event of the ``Tree`` component's "Create
new page" button. EXT:form uses this event to display the "new page" dialog
box.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = targetEvent
    * @return void
    */
   getPublisherSubscriber().subscribe('view/structure/button/newPage/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-structure-renew-postprocess:

view/structure/renew/postProcess
++++++++++++++++++++++++++++++++

This event is called from the view-model after the ``Tree`` component has been
re-rendered. EXT:form uses this event to display potential validation errors
from form elements in the ``Tree`` component.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/structure/renew/postProcess', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-structure-root-selected:

view/structure/root/selected
++++++++++++++++++++++++++++

This event is called if the root form element in the ``Tree`` component is
clicked. EXT:form uses this event to re-render the ``Stage``, ``Inspector`` and
``Tree`` components.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/structure/root/selected', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-tree-dnd-change:

view/tree/dnd/change
++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'change' event from 'jquery.mjs.nestedSortable' calls
the ``view/tree/dnd/change`` event in der ``Tree`` component if form elements
are sorted. EXT:form uses this event to set various CSS classes during the drag
-and-drop process.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = placeholderDomElement
    *              args[1] = parentFormElementIdentifierPath
    *              args[2] = enclosingCompositeFormElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/tree/dnd/change', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-tree-dnd-stop:

view/tree/dnd/stop
++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'stop' event from 'jquery.mjs.nestedSortable' calls the
``view/tree/dnd/stop`` event in the ``Tree`` component if form elements are
sorted. EXT:form uses this event to re-render ``Tree``, ``Stage`` and
``Inspector`` components at the end of the drag-and-drop process and to select
the moved form element.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = draggedFormElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/tree/dnd/stop', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-tree-dnd-update:

view/tree/dnd/update
++++++++++++++++++++

EXT:form uses the jQuery plugin 'jquery.mjs.nestedSortable' for the drag-and-
drop functionality. The 'update' event from 'jquery.mjs.nestedSortable' calls
the ``view/tree/dnd/update`` event in der ``Tree`` component if form elements
are sorted. EXT:form uses this event to move the form element in the ``form
definition`` accordingly at the end of the drag-and-drop process.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = movedDomElement
    *              args[1] = movedFormElementIdentifierPath
    *              args[2] = previousFormElementIdentifierPath
    *              args[3] = nextFormElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/tree/dnd/update', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-tree-node-clicked:

view/tree/node/clicked
++++++++++++++++++++++

This event is called from the ``Tree`` component if a form element is clicked.
EXT:form uses this event to re-render the ``Stage`` and ``Inspector``
components and select the form element.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = formElementIdentifierPath
    * @return void
    */
   getPublisherSubscriber().subscribe('view/tree/node/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-tree-render-listitemadded:

view/tree/render/listItemAdded
++++++++++++++++++++++++++++++

This event is called by the ``Tree`` component for each form element as soon as
it is added to the tree.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    *              args[0] = listItem
    *              args[1] = formElement
    * @return void
    */
   getPublisherSubscriber().subscribe('view/tree/render/listItemAdded', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-undobutton-clicked:

view/undoButton/clicked
+++++++++++++++++++++++

This event is called when the undo button is clicked in the ``form editor``
header. The history of adding / deleting and moving form elements and property
collection elements (validators/ finishers) is stored in an internal stack to
implement the undo / redo functionality. EXT:form uses this event to set this
stack to the next state.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/undoButton/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-viewmodebutton-abstract-clicked:

view/viewModeButton/abstract/clicked
++++++++++++++++++++++++++++++++++++

This event is called when the abstract view button is clicked in the header
area of the ``Stage`` component. EXT:form uses this event to render the
``abstract view`` in the ``Stage`` component.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/viewModeButton/abstract/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-events-view-viewmodebutton-preview-clicked:

view/viewModeButton/preview/clicked
+++++++++++++++++++++++++++++++++++

This event is called when the preview button is clicked in the header area of
the ``Stage`` component. EXT:form uses this event to render the ``preview
view`` in the ``Stage`` component.

Subscribe to the event:

.. code-block:: javascript

   /**
    * @private
    *
    * @param string
    * @param array
    * @return void
    */
   getPublisherSubscriber().subscribe('view/viewModeButton/preview/clicked', function(topic, args) {
   });


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel:

FormElement model
^^^^^^^^^^^^^^^^^


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-property-parentrenderable:

Property: __parentRenderable
++++++++++++++++++++++++++++

__parentRenderable includes the parent element as ``FormElement model``.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-property-identifierpath:

Property: __identifierPath
++++++++++++++++++++++++++

Internally, all form elements are identified by their 'identifier' property,
which must be unique for each form. The ``__identifierPath`` property contains
the path to the element (as seen from the first element), separated by a ``/``.
Using this path, you can access the element directly through an API method.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-get:

Method: get()
+++++++++++++

Each property of the ``FormElement model`` can be accessed by the ``get()``
method through the property path (separated by ``.``). Prerequisite for this
is that all levels up to the target property are objects.

Example of a ``FormElement model``:

.. code-block:: javascript

   {
     "identifier": "name",
     "defaultValue": "",
     "label": "Name",
     "type": "Text",
     "properties": {
       "fluidAdditionalAttributes": {
         "placeholder": "Name"
       }
     },
     "__parentRenderable": "example-form/page-1 (filtered)",
     "__identifierPath": "example-form/page-1/name",
     "validators": [
       {
         "identifier": "NotEmpty"
       }
     ]
   }

Access to ``properties.fluidAdditionalAttributes.placeholder``:

.. code-block:: javascript

   // value = 'Name'
   var value = getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name').get('properties.fluidAdditionalAttributes.placeholder');

Two exceptions are the two arrays of "finishers" / "validators" (``property
collections``) and the ``renderables``.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-get-propertycollectionproperties:

Accessing property collection properties
''''''''''''''''''''''''''''''''''''''''

Property collection are identified as form elements through the property
``identifier``. Because property collection properties are in an array and
their positions in the array are potentially unknown, the ``getFormEditorApp().buildPropertyPath()``
method exists. This can be used to access a property of a property collection
item via its ``identifier``.

Example of a ``FormElement model``:

.. code-block:: javascript

   {
     "identifier": "name",
     "defaultValue": "",
     "label": "Name",
     "type": "Text",
     "properties": {
       "fluidAdditionalAttributes": {
         "placeholder": "Name"
       }
     },
     "__parentRenderable": "example-form/page-1 (filtered)",
     "__identifierPath": "example-form/page-1/name",
     "validators": [
       {
         "identifier": "StringLength"
         "options": {
           "minimum": "1",
           "maximum": "2"
         }
       }
     ]
   }

Access to ``options.minimum`` of the validator ``StringLength``:

.. code-block:: javascript

   var formElement = getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name');
   var propertyPath = getFormEditorApp().buildPropertyPath('options.minimum', 'StringLength', 'validators', formElement);
   // value = 1
   var value = formElement.get(propertyPath);


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-get-renderables:

Accessing renderables
'''''''''''''''''''''

Like ``property collections``, ``renderables`` (the child elements) are also in
an array and their position in the array is potentially unknown. Direct access
to child elements through the  ``get()`` method is impossible.
``formElement.get('renderables')`` supplies an array with the ``FormElement
models`` of the child elements. You must then loop over this array. Access to a
specific child element should be done using ``getFormEditorApp().getFormElementByIdentifierPath()``.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-set:

Method: set()
+++++++++++++

Any property of the ``FormElement model`` can be written using the ``set()``
method by means of the property path (separated by ``.``).

Example of a ``FormElement model``:

.. code-block:: javascript

   {
     "identifier": "name",
     "defaultValue": "",
     "label": "Name",
     "type": "Text",
     "properties": {
       "fluidAdditionalAttributes": {
         "placeholder": "Name"
       }
     },
     "__parentRenderable": "example-form/page-1 (filtered)",
     "__identifierPath": "example-form/page-1/name",
     "validators": [
       {
         "identifier": "NotEmpty"
       }
     ]
   }

Set the property ``properties.fluidAdditionalAttributes.placeholder``:

.. code-block:: javascript

   getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name').set('properties.fluidAdditionalAttributes.placeholder', 'New Placeholder');

Example of the ``FormElement model`` after the ``set()`` operation:

.. code-block:: javascript

   {
     "identifier": "name",
     "defaultValue": "",
     "label": "Name",
     "type": "Text",
     "properties": {
       "fluidAdditionalAttributes": {
         "placeholder": "New Placeholder"
       }
     },
     "__parentRenderable": "example-form/page-1 (filtered)",
     "__identifierPath": "example-form/page-1/name",
     "validators": [
       {
         "identifier": "NotEmpty"
       }
     ]
   }

Two exceptions are the two arrays of "finishers" / "validators" (``property
collections``) and the ``renderables``.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-set-propertycollectionproperties:

Setting property collection properties
''''''''''''''''''''''''''''''''''''''

In principle, the same applies here as for :ref:`get property collection properties<apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-get-propertycollectionproperties>`.

Set the property ``options.minimum`` of the validator ``StringLength``:

.. code-block:: javascript

   var formElement = getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name');
   var propertyPath = getFormEditorApp().buildPropertyPath('options.minimum', 'StringLength', 'validators', formElement);
   formElement.set(propertyPath, '2');


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-set-renderables:

Setting renderables
'''''''''''''''''''

To add child form elements to a ``FormElement model``, the appropriate API
methods should be used:

- getFormEditorApp().createAndAddFormElement()
- getFormEditorApp().addFormElement()
- getFormEditorApp().moveFormElement()
- getFormEditorApp().removeFormElement()


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-unset:

Method: unset()
+++++++++++++++

Any property of the ``FormElement model`` can be deleted using the method
``unset()`` by means of the property path (separated by ``.``).

Example of a ``FormElement model``:

.. code-block:: javascript

   {
     "identifier": "name",
     "defaultValue": "",
     "label": "Name",
     "type": "Text",
     "properties": {
       "fluidAdditionalAttributes": {
         "placeholder": "Name"
       }
     },
     "__parentRenderable": "example-form/page-1 (filtered)",
     "__identifierPath": "example-form/page-1/name",
     "validators": [
       {
         "identifier": "NotEmpty"
       }
     ]
   }

Delete the property ``properties.fluidAdditionalAttributes.placeholder``:

.. code-block:: javascript

   // value = 'Name'
   var value = getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name').unset('properties.fluidAdditionalAttributes.placeholder');

Example of the ``FormElement model`` after the ``unset()`` operation:

.. code-block:: javascript

   {
     "identifier": "name",
     "defaultValue": "",
     "label": "Name",
     "type": "Text",
     "properties": {
       "fluidAdditionalAttributes": {}
     },
     "__parentRenderable": "example-form/page-1 (filtered)",
     "__identifierPath": "example-form/page-1/name",
     "validators": [
       {
         "identifier": "NotEmpty"
       }
     ]
   }

Two exceptions are the two arrays of "finishers" / "validators" (``property
collections``) and the ``renderables``.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-unset-propertycollectionproperties:

Remove property collection properties
'''''''''''''''''''''''''''''''''''''

In principle, the same applies here as for :ref:`get property collection properties<apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-get-propertycollectionproperties>`.

Delete the property ``options.minimum`` of the validator ``StringLength``:

.. code-block:: javascript

   var formElement = getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name');
   var propertyPath = getFormEditorApp().buildPropertyPath('options.minimum', 'StringLength', 'validators', formElement);
   formElement.unset(propertyPath);


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-unset-renderables:

Remove renderables
''''''''''''''''''

To delete a ``FormElement model``, the corresponding API method
``getFormEditorApp().removeFormElement()`` should be used.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-on:

Method: on()
++++++++++++

Any number of :ref:`Publisher/Subscriber<concepts-formeditor-basicjavascriptconcepts-events>`
events can be assigned to any property path of a ``FormElement model``. Each
``set()`` operation on this property path will then call these events. By
default, EXT:form registers the event :ref:`core/formElement/somePropertyChanged<apireference-formeditor-basicjavascriptconcepts-events-core-formelement-somepropertychanged>`
for each property path.

Example:

.. code-block:: javascript

   getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name').on('properties.fluidAdditionalAttributes.placeholder', 'my/custom/event');
   getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name').set('properties.fluidAdditionalAttributes.placeholder', 'New Placeholder');
   // now, the event 'my/custom/event' will be published


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-off:

Method: off()
+++++++++++++

Any event registered via :ref:`on()<apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-on>`
can be removed with off().

Example:

.. code-block:: javascript

   getFormEditorApp().getFormElementByIdentifierPath('example-form/page-1/name').off('properties.fluidAdditionalAttributes.placeholder', 'my/custom/event');


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-getobjectdata:

Method: getObjectData()
+++++++++++++++++++++++

All ``FormElement model`` properties are private and cannot be manipulated
directly from the outside. They can only be accessed via ``set()`` or
``get()``. This method is used internally to obtain all data of a ``FormElement
model`` in object form so that they can be used in, for example, Ajax requests.
``getObjectData()`` returns a dereferenced object of the ``FormElement model``
with all internal data, thus allowing read access to all data set via
``set()``.


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-tostring:

Method: toString()
++++++++++++++++++

A method that was implemented for debugging purposes. Returns the object data
supplied by ``getObjectData()`` in string form.

.. code-block:: javascript

   console.log(formElement.toString());


.. _apireference-formeditor-basicjavascriptconcepts-formelementmodel-method-clone:

Method: clone()
+++++++++++++++

If necessary, a form element can be cloned. Returns a dereferenced clone of the
original ``FormElement model``.


.. code-block:: javascript

   var dolly = formElement.clone();
