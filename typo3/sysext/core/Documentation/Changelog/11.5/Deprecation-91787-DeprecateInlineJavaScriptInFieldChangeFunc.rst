.. include:: /Includes.rst.txt

==========================================================
Deprecation: #91787 - Inline JavaScript in fieldChangeFunc
==========================================================

See :issue:`91787`

Description
===========

Custom :php:`FormEngine` nodes allow to use internal property :php:`fieldChangeFunc`
to add or modify client-side JavaScript behavior when field values are changed.

In the past these declarations basically were inline JavaScript, provided in
PHP and forwarded to the browser via HTML :html:`onchange` or :html:`onclick`
event attributes. In favor of introducing content security policy headers and
to reduce inline JavaScript, those functionality shall be defined in a
structured way & custom client-side behavior shall be provided by corresponding
JavaScript modules instead.

As a result, :php:`fieldChangeFunc` declarations are not using plain inline
JavaScript (as scalar :php:`string`) anymore, but make use of corresponding objects
implementing new :php:`\TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeInterface`.
This interface provides both a new structured and declarative approach via
`JSON` - but also allows to fallback to legacy inline JavaScript in case it
is required in combination with legacy 3rd party extensions.

Using :php:`fieldChangeFunc` with scalar :php:`string` values has been marked as deprecated and has to
be substituted with specific implementations of :php:`OnFieldChangeInterface`.


Impact
======

Using :php:`fieldChangeFunc` with scalar :php:`string` values will trigger a
PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

Installations implementing custom :php:`FormEngine` components (wizards, nodes,
render-types, ...) that provide inline JavaScript using :php:`fieldChangeFunc`.

.. code-block:: php

    // examples
    $this->data['parameterArray']['fieldChangeFunc']['example'] = "alert('demo');";
    $parameterArray['fieldChangeFunc']['example'] = "alert('demo');";


Migration
=========

The following steps provide a brief overview of the new components in order to
avoid inline JavaScript. A complete and installable example is available with
`ext:demo_91787 <https://github.com/ohader/demo_91787>`__.


PHP :php:`OnFieldChangeInterface` instance
------------------------------------------

.. code-block:: php

    <?php
    namespace TYPO3\Example;

    use TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeInterface;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    class AlertOnFieldChange implements OnFieldChangeInterface
    {
        protected string $value = 'demo';
        public function __toString(): string
        {
            // provides `alert('demo')` as plain inline JavaScript
            return sprintf(
                'alert(%s)',
                // always make sure to encode data, mitigating XSS
                GeneralUtility::quoteJSvalue($this->value)
            );
        }
        public function toArray(): array
        {
            // provides structured representation
            return [
                // handler `name` as registered with `FormEngine.js`
                'name' => 'example-alert',
                // fixed `data` segment
                'data' => [
                    // ... can contain any arbitrary & custom payload
                    'value' => $this->value,
                ]
            ];
        }
    }


PHP :php:`FormEngine` consumer
------------------------------

.. code-block:: php

    <?php
    namespace TYPO3\Example;

    use TYPO3\CMS\Backend\Form\Element\InputTextElement;
    use TYPO3\CMS\Core\Page\PageRenderer;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    // just extending `input` TCA render-type, to keep it simple
    class ConsumingElement extends InputTextElement
    {
        public function render()
        {
            // uses custom `OnFieldChangeInterface` implementation from above
            // (whenever the value of this field is changed, an alert message shall be shown)
            $this->data['parameterArray']['fieldChangeFunc']['example'] = new AlertOnFieldChange();
            // side-note: before having `OnFieldChangeInterface`, it looked like this using inline code
            // $this->data['parameterArray']['fieldChangeFunc']['example'] = "alert('demo');";

            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            // registers RequireJS module to register & handle that `fieldChangeFunc` instruction
            // (JavaScript module is loaded from `ext:example/Resources/Public/JavaScript/Demo.js`)
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Example/Demo');

            // just use parent method to render that `<input type="text">` field
            return parent::render();
        }
    }


JavaScript :js:`FormEngine` registration
----------------------------------------

JavaScript module :js:`TYPO3/CMS/Example/Demo` is fetched via RequireJS from
resource path :file:`ext:example/Resources/Public/JavaScript/Demo.js`.

.. code-block:: javascript

    define(['TYPO3/CMS/Backend/FormEngine'], (FormEngine) => {
        FormEngine.registerOnFieldChangeHandler(
            // `example-alert` as defined in `name` segment from PHP `AlertOnFieldChange::toArray()`
            'example-alert',
            // `data` segment from PHP `AlertOnFieldChange::toArray()`
            (data) => { alert(data.title); }
        );
    })


.. index:: Backend, JavaScript, TCA, NotScanned, ext:backend
