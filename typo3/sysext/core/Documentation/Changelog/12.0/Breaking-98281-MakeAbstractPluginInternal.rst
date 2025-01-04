.. include:: /Includes.rst.txt

.. _breaking-98281-1662549900:

================================================
Breaking: #98281 - Make AbstractPlugin @internal
================================================

See :issue:`98281`

Description
===========

Extending the class :php:`\TYPO3\CMS\Frontend\Plugin\AbstractPlugin` is not a
recommended way of developing Frontend plugins anymore. This class is not
maintained anymore and may be removed in future versions without further notice.

The TypoScript property :typoscript:`plugin.tx_myextension_pi1._DEFAULT_PI_VARS`
has only been used in the class :php:`AbstractPlugin`. It is therefore not public
API anymore.

Impact
======

Plugins based on :php:`\TYPO3\CMS\Frontend\Plugin\AbstractPlugin` will
continue to function. However, there will be warnings about using internal
code displayed in most IDEs.

:typoscript:`_DEFAULT_PI_VARS` has been removed from syntax highlighting as it is
not public API anymore.

Affected installations
======================

All extensions having classes that extend
:php:`\TYPO3\CMS\Frontend\Plugin\AbstractPlugin` are affected.

Migration
=========

Remove the dependency of :php:`\TYPO3\CMS\Frontend\Plugin\AbstractPlugin`. If
functionality of this class is still used, copy it into your plugin.

Example
-------

Class before migration:

..  code-block:: php
    :caption: EXT:gh_randomcontent/Classes/Plugin/RandomContent.php

    use Psr\Http\Message\ServerRequestInterface;

    class RandomContent extends AbstractPlugin
    {
        public function main(
          string $content,
          array $conf,
          ServerRequestInterface $request,
        ): string
        {
            $this->conf = $conf;

            // Init FlexForm configuration for plugin
            $this->pi_initPIflexForm();
            if ($this->pi_getFFvalue(
                $this->cObj->data['pi_flexform'],
                'which_pages', 'sDEF')
            ) {
                $this->conf['pages'] = $this->pi_getFFvalue(
                    $this->cObj->data['pi_flexform'],
                    'which_pages', 'sDEF'
                );
            }
            // ...
        }
    }

Class after migration:

..  code-block:: php
    :caption: EXT:gh_randomcontent/Classes/Plugin/RandomContent.php

    use Psr\Http\Message\ServerRequestInterface;

    class RandomContent
    {
        /**
         * The back-reference to the mother cObj object set at call time
         */
        public $cObj;

        /**
         * This setter is called when the plugin is called from UserContentObject (USER)
         * via ContentObjectRenderer->callUserFunction().
         *
         * @param ContentObjectRenderer $cObj
         */
        public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
        {
            $this->cObj = $cObj;
        }

        public function main(
          string $content,
          array $conf,
          ServerRequestInterface $request,
        ): string
        {
            $this->conf = $conf;

            $this->pi_initPIflexForm(); // Init FlexForm configuration for plugin
            if ($this->pi_getFFvalue($this->cObj->data['pi_flexform'],
                'which_pages', 'sDEF')) {
                $this->conf['pages'] = $this->pi_getFFvalue(
                    $this->cObj->data['pi_flexform'],
                    'which_pages',
                    'sDEF'
                );
            }
            // ...
        }

        /**
         * Converts $this->cObj->data['pi_flexform'] from XML string to FlexForm array.
         *
         * @param string $field Field name to convert
         */
        public function pi_initPIflexForm($field = 'pi_flexform')
        {
            // ...
        }

        public function pi_getFFvalue(
            $T3FlexForm_array,
            $fieldName,
            $sheet = 'sDEF',
            $lang = 'lDEF',
            $value = 'vDEF'
        ) {
            // ...
        }
    }

It is also possible to migrate to an Extbase plugin using a controller.
See the :ref:`Extbase documentation, chapter
"Frontend Plugins" <t3coreapi:extbase_registration_of_frontend_plugins>`.

..  important::

    The `AbstractPlugin` is :ref:`deprecated <deprecation-100639-1681740974>`
    through a follow-up change and will be removed in TYPO3 v13. Executing
    a plugin via the TypoScript userFunc (also utilized the `$request` argument for
    the `main` method) continues to work, as long as the collection of `AbstractPlugin`
    methods are ported to the custom user class.

.. index:: Frontend, FullyScanned, ext:frontend
