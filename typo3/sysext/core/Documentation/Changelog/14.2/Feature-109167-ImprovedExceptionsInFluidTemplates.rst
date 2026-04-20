..  include:: /Includes.rst.txt

..  _feature-109167-1773174150:

=========================================================
Feature: #109167 - Improved exceptions in Fluid templates
=========================================================

See :issue:`109167`

Description
===========

In an effort to simplify debugging Fluid templates, TYPO3 14 enhances
exception messages thrown by Fluid in several ways:

*   Templates that contain invalid syntax or refer to undeclared ViewHelper
    arguments now contain both the full path to the template file and the
    affected line number in that file.
*   Most ViewHelper-related error messages now contain the full path to the
    template file.
*   Fluid Standalone 5.2 (also backported to Fluid 4.6) introduces more granular
    exception classes that can be used by ViewHelpers to classify runtime errors.
    These classifications are also part of the error message.
*   When a referenced Fluid template cannot be found, the exception message
    contains a full list of the candidates that have been tried. Also,
    the exception contains the context in which the template file is missing
    (for example `FLUIDTEMPLATE` or `PAGEVIEW`).

In order for this to work with custom ViewHelper implementations, ViewHelpers
need to use the base ViewHelper exception class or one of its child classes:

*   :php:`\TYPO3Fluid\Fluid\Core\ViewHelper\Exception` for general exceptions
*   :php:`\TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentException` for
    general exceptions related to ViewHelper arguments
*   :php:`\TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentValueException` for
    invalid ViewHelper argument values (e.g. wrong type, empty, invalid format)
*   :php:`\TYPO3Fluid\Fluid\Core\ViewHelper\MissingArgumentException` if
    a required ViewHelper argument has not been supplied
*   :php:`\TYPO3Fluid\Fluid\Core\ViewHelper\UndeclaredArgumentException` if
    a ViewHelper is called with an argument that has not been defined

If any of these exception classes are used in a ViewHelper, Fluid's internal
error handler automatically adds the full path to the current template file
to the exception. It is not necessary for ViewHelpers to do this themselves.
Note that this leads to nested exceptions. The original exception can be
accessed via :php:`$e->getPrevious()`.

Examples
--------

..  code-block:: plaintext
    :caption: Parse error in template

    #1238169398 TYPO3Fluid\Fluid\Core\Parser\Exception
    Fluid parse error in template /var/www/html/typo3conf/ext/theme/Resources/Private/Components/Test/Test.fluid.html, line 11 at character 15.
    Error: Not all tags were closed! (error code 1238169398). Template source chunk: test

..  code-block:: plaintext
    :caption: Undeclared ViewHelper argument

    #1773227091 TYPO3Fluid\Fluid\Core\ViewHelper\Exception
    TYPO3Fluid\Fluid\Core\ViewHelper\UndeclaredArgumentException in /var/www/html/typo3conf/ext/theme/Resources/Private/Components/Test/Test.fluid.html:
    Undeclared arguments passed to ViewHelper TYPO3Fluid\Fluid\ViewHelpers\Format\TrimViewHelper: foo. Valid arguments are: value, characters, side
    (/var/www/html/vendor/typo3fluid/fluid/src/Core/ViewHelper/AbstractViewHelper.php:314)

..  code-block:: plaintext
    :caption: Custom validation by ViewHelper implementation

    #1669191560 TYPO3Fluid\Fluid\Core\ViewHelper\Exception
    TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentValueException in /var/www/html/typo3conf/ext/theme/Resources/Private/Components/Test/Test.fluid.html:
    The side "none" supplied to Fluid's format.trim ViewHelper is not supported.
    (/var/www/html/vendor/typo3fluid/fluid/src/ViewHelpers/Format/TrimViewHelper.php:118)

..  code-block:: plaintext
    :caption: Missing template file for PAGEVIEW

    #1742058289 TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException
    PAGEVIEW TypoScript object: Failed to resolve a template file for page layout "default". See also: https://docs.typo3.org/permalink/t3tsref:cobj-pageview@14.2.
    The following paths were checked:
    "/var/www/html/typo3conf/ext/dummy/Resources/Private/Templates/Pages/Default/default.fluid.html",
    "/var/www/html/typo3conf/ext/dummy/Resources/Private/Templates/Pages/Default/default.html",
    "/var/www/html/typo3conf/ext/dummy/Resources/Private/Templates/Pages/Default/default",
    "/var/www/html/typo3conf/ext/dummy/Resources/Private/Templates/Pages/Default/Default.fluid.html",
    "/var/www/html/typo3conf/ext/dummy/Resources/Private/Templates/Pages/Default/Default.html",
    "/var/www/html/typo3conf/ext/dummy/Resources/Private/Templates/Pages/Default/Default",
    "/var/www/html/typo3conf/ext/dummy/Resources/Private/Templates/Pages/default.fluid.html",
    "/var/www/html/typo3conf/ext/dummy/Resources/Private/Templates/Pages/default.html",
    "/var/www/html/typo3conf/ext/dummy/Resources/Private/Templates/Pages/default",
    "/var/www/html/typo3conf/ext/dummy/Resources/Private/Templates/Pages/Default.fluid.html",
    "/var/www/html/typo3conf/ext/dummy/Resources/Private/Templates/Pages/Default.html",
    "/var/www/html/typo3conf/ext/dummy/Resources/Private/Templates/Pages/Default"

Impact
======

To make debugging easier, exceptions that originate from Fluid templates now
contain more context, such as the full path to the template file.

..  index:: Fluid, ext:fluid
