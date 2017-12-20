.. include:: ../../Includes.txt

==============================================
Feature: #79440 - FormEngine Element Expansion
==============================================

See :issue:`79440`

Description
===========

A new API in FormEngine has been introduced that allows fine grained additions to
single elements and containers without substituting the whole element.

For elements within the :code:`TCA` config section, three new options have been introduced:

* :code:`fieldInformation` An array of single field information. This could be additionally describing text
  that is rendered between the element label and the element itself. Field information are restricted, only
  a couple of HTML tags are allowed within the result HTML.
* :code:`fieldControl` An array of single field controls. These are icons with JavaScript or
  links to further functionality of the framework. They are usually displayed next to the element. Each control
  must return an icon identifier, a title, and an array of a-tag attributes.
* :code:`fieldWizard` Additional functionality enriching the element. These are typically shown
  below the element. Wizards may return any HTML.

For FormEngine containers, the same API has been introduced, but it is currently only implemented within
the :code:`OuterWrapContainer` which renders the record title and delegates the main record rendering to
a different container. Adding :code:`fieldInformation` or :code:`fieldWizard` here allows embedding additional
functionality between the record title an the main record body.

Single elements and containers may register default information, control and wizards. The configuration is merged
with any possibly given configuration from `TCA`.

Example from :code:`GroupElement`:

.. code-block:: php

    class GroupElement extends AbstractFormElement
    {
        /**
         * Default field controls for this element.
         *
         * @var array
         */
        protected $defaultFieldControl = [
            'elementBrowser' => [
                'renderType' => 'elementBrowser',
            ],
            'insertClipboard' => [
                'renderType' => 'insertClipboard',
                'after' => [ 'elementBrowser' ],
            ],
            'editPopup' => [
                'renderType' => 'editPopup',
                'disabled' => true,
                'after' => [ 'insertClipboard' ],
            ],
            'addRecord' => [
                'renderType' => 'addRecord',
                'disabled' => true,
                'after' => [ 'editPopup' ],
            ],
            'listModule' => [
                'renderType' => 'listModule',
                'disabled' => true,
                'after' => [ 'addRecord' ],
            ],
        ];

        public function render()
        {
            ...
            $fieldControlResult = $this->renderFieldControl();
            $fieldControlHtml = $legacyFieldControlHtml . $fieldControlResult['html'];
            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);
        }
    }

This element registers five default field controls (icons next to the element), renders them and later
adds the HTML at an appropriate place within the HTML of the main element. The :code:`defaultFieldControl` can
be overwritten on :code:`TCA` level of single fields:

.. code-block:: php

    'columns' => [
        'aField' => [
            'label' => 'aField',
            'config' => [
                'fieldControl' => [
                    'elementBrowser' => [
                        'disabled' => true,
                    ],
                    'editPopup' => [
                        'disabled' => false,
                    ],
                    'aNewControl' => [
                        'renderType' => 'myOwnTypeGroupControl',
                        'before' => [ 'elementBrowser' ],
                    ],
                ],
            ],
        ],
    ],


The above configuration disables the element browser which is enabled by default, it enabled the edit popup
control which exists in default configuration but is disabled by default, and it adds a further control called
:code:`aNewControl` with :code:`renderType=myOwnTypeGroupControl`. The renderType instructs the FormEngine
:code:`NodeFactory` to instantiate the class that in configured for that renderType, identical to other usages
of the NodeFactory, this lookup can be manipulated on configuration and code level. In the example above, a new
renderType should be registered in :code:`ext_localconf.php`:

.. code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1485351217] = [
        'nodeName' => 'myOwnTypeGroupControl',
        'priority' => 30,
        'class' => \My\ExtensionName\Form\FieldControl\MyFancyControl::class,
    ];


Note the above configuration also uses the :code:`DependencyResolver`: It is possible to resort single
elements by adding :code:`before` and :code:`after` to the configuration. In above example, the new
control :code:`aNewControl` would be shown as first control, before :code:`elementBrowser`.

The first level below :code:`fieldControl` contains speaking names of single controls, each control must
have a :code:`renderType` defined (either on TCA level or via defaultFieldControl), and they may have
a :code:`before` and :code:`after` and a :code:`disabled` setting. Each control also may have a :code:`options`
sub array with further settings given to the specific control.

In :code:`TCA`, the configuration name for field controls is :code:`fieldControl`, for wizards it is :code:`fieldWizard`,
and for information it is :code:`fieldInformation`. All three follow the same structure. It is up to a single element
if all three of these are actually called and rendered. For instance, it sometimes does not make sense to have
field controls in all elements, so some elements skip that.

For containers, the configuration of :code:`fieldInformation`, :code:`fieldControl` and :code:`fieldWizard` is within
the :code:`ctrl` section of :code:`TCA`. This is currently only implemented within the :code:`OuterWrapContainer` for
:code:`fieldInformation` and :code:`fieldWizard`.

Example:

.. code-block:: php

    'ctrl' => [
        ...
        'container' => [
            'outerWrapContainer' => [
                'fieldInformation' => [
                    'myHelloWorld' => [
                        'renderType' => 'helloWorld',
                    ],
                ],
            ],
        ],
    ],


The above example would instruct the system to call the class registered for renderType :code:`helloWorld`
within the OuterWrapContainer.


Impact
======

The new API brings lots of new options to add functionality to single elements
without substituting the full element.


.. index:: Backend, TCA, PHP-API