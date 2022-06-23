.. include:: /Includes.rst.txt


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
If nothing is specified, the configuration (``form definition`` or ``overrideConfiguration``) is searched for the prototype's name.
If no specification exists, the standard prototype ``standard`` is used.



.. _apireference-frontendrendering-programmatically:

Build forms programmatically
----------------------------

Implement a ``FormFactory`` and build the form::

   declare(strict_types = 1);
   namespace VENDOR\MySitePackage\Domain\Factory;

   use TYPO3\CMS\Core\Utility\GeneralUtility;
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
           $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
           $prototypeConfiguration = $configurationService->getPrototypeConfiguration($prototypeName);

           $form = GeneralUtility::makeInstance(FormDefinition::class, 'MyCustomForm', $prototypeConfiguration);
           $form->setRenderingOption('controllerAction', 'index');

           $page1 = $form->createPage('page1');
           $name = $page1->createElement('name', 'Text');
           $name->setLabel('Name');
           $name->addValidator(GeneralUtility::makeInstance(NotEmptyValidator::class));

           $page2 = $form->createPage('page2');
           $message = $page2->createElement('message', 'Textarea');
           $message->setLabel('Message');
           $message->addValidator(GeneralUtility::makeInstance(StringLengthValidator::class, ['minimum' => 5, 'maximum' => 20]));

           // Creating a RadioButton/MultiCheckbox
           $page3 = $form->createPage('page3');
           $radio = $page3->createElement('checkbox', 'RadioButton');
           $radio->setProperty('options', ['value1' => 'Label1', 'value2' => 'Label2']);
           $radio->setLabel('My Radio ...');

           $form->createFinisher('EmailToSender', [
               'subject' => 'Hello',
               'recipients' => [
                   'your.company@example.com' => 'Your Company name'
               ],
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

   $form = $formDefinition->bind($this->request);
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

   public function bind(Request $request): FormRuntime;


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
