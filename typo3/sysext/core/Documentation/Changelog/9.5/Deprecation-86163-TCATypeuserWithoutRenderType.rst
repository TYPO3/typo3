.. include:: ../../Includes.txt

========================================================
Deprecation: #86163 - TCA type="user" without renderType
========================================================

See :issue:`86163`

Description
===========

The following :php:`TCA` properties on :php:`type="user"` config types have been marked as deprecated and
should not be used any longer:

* :php:`userFunc`
* :php:`parameters`
* :php:`noTableWrapping`


Impact
======

This especially means that :php:`userFunc` should not be used any longer on :php:`TCA` fields
registered as config type :php:`user`. Those can be substituted with a custom :php:`renderType`
since TYPO3 v7. See example below for more details.


Affected Installations
======================

Instances are affected if an extension registers a :php:`type=user` :php:`TCA` config type with a
custom :php:`userFunc`. If a field uses the :php:`userFunc` property, a PHP :php:`E_USER_DEPRECATED`
error is triggered during rendering.


Migration
=========

:php:`userFunc` implementations can switch to use a custom :php:`renderType` as outlined
in the :ref:`FormEngine documentation <t3coreapi:FormEngine-Rendering-NodeFactory>`. The TYPO3 core
did that for example with the `is_public` field of table `sys_file_storge` in patch 58141_.

To switch from a :php:`userFunc` implementation to a :php:`renderType`, an extension typically has
to register an own element node in :file:`ext_localconf.php`. Then change the user function to a class
that extends :php:`AbstractFormElement` where method :php:`render()` returns an array as defined
in helper method :php:`initializeResultArray`. The `HTML` created by the former user function should be
returned in :php:`$resultArray['html']`, parameters like the `tableName` can be found in :php:`$this->data`.

Note the `renderType` variant can additionally load custom `JavaScript` and `CSS` using further parts of the
result array, typically :php:`requireJsModules` and :php:`stylesheetFiles`. Arguments to the element
can be defined by using any property within the `config` section, it is up to the specific `renderType` to
do this, using `parameters` as property key is probably a good idea, though.

As example, imagine a :php:`TCA` user element has been defined like this in the `columns` section::

    'myMapElement' = [
        'label' => 'My map element'
        'config' => [
            'type' => 'user',
            'userFunc' => 'Vendor\Extension\Tca\UserFunc\MyMap->render',
            'parameters' => [
                'useOpenStreetMap' => true,
            ],
        ],
    ],

This should be adapted to a registered node element class::

    // Register a node in ext_localconf.php
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][<unix timestamp of "now">] = [
        'nodeName' => 'customMapElement',
        'priority' => 40,
        'class' => \Vendor\Extension\Form\Element\CustomMapElement::class,
    ];

With a `TCA` registration like this to delegate the element rendering to the registered class::

    'myMapElement' = [
        'label' => 'My map element'
        'config' => [
            'type' => 'user',
            'renderType' => 'customMapElement',
            'parameters' => [
                'useOpenStreetMap' => true,
            ],
        ],
    ],

And a class implementation that extends :php:`AbstractFormElement`::

    <?php
    declare(strict_types = 1);
    namespace Vendor\Extension\Form\Element;

    use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;

    class CustomMapElement extends AbstractFormElement
    {
        public function render()
        {
            // Custom TCA properties and other data can be found in $this->data, for example the above
            // parameters are available in $this->data['parameterArray']['fieldConf']['config']['parameters']
            $result = $this->initializeResultArray();
            $result['html'] = 'my map content';
            return $result;
        }
    }

.. _58141: https://review.typo3.org/#/c/58141/

.. index:: Backend, TCA, NotScanned
