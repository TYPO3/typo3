..  include:: /Includes.rst.txt

..  _feature-106945-1750757664:

===============================================================
Feature: #106945 - Allow usage of Symfony validators in Extbase
===============================================================

See :issue:`106945`

Description
===========

Extbase models and controllers now support the usage of
`Symfony Validators <https://symfony.com/doc/current/components/validator.html>`__.
Validators are built from Symfony Constraints which can be added as attributes
to domain properties and controller methods. Once a constraint attribute is
detected when reflecting properties and methods, it will be decorated by a new
:php:`ConstraintDecoratingValidator` class, which is compatible to Extbase's
:php:`ValidatorInterface`.

Decorated constraints may contain localizable messages. If a resulting message
contains valid `LLL:` syntax, the localization label will be translated
properly. The decorating validator also takes care of messages parameters and
internally converts them from named parameters such as `{{ value }}` to
:php:`sprintf`-compatible parameters like `%1$s`.

..  important::

    Most of the available constraints such as :php:`#[NotBlank]` and
    :php:`#[Regex]` can be used. However, more complex constraints like
    :php:`#[File]` or :php:`#[Image]` are currently not compatible to the
    available implementation within Extbase, since they are very strong bound to
    the Symfony Framework. Compatibility with those validators and constraints
    may be added in the future.

Example
-------

..  code-block:: php
    :caption: EXT:my_extension/Classes/Domain/Model/MyModel.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\Domain\Model;

    use Symfony\Component\Validator\Constraints as Assert;
    use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

    class MyModel extends AbstractEntity
    {
        #[Assert\WordCount(max: 200, maxMessage: 'Biography must not exceed 200 words.')]
        protected string $biography = '';

        #[Assert\CssColor(message: 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:validator.avatarColor.error')]
        protected string $avatarColor = '';

        #[Assert\Iban]
        protected string $iban = '';

        public function getBiography(): string
        {
            return $this->biography;
        }

        public function setBiography(string $biography): void
        {
            $this->biography = $biography;
        }

        public function getAvatarColor(): string
        {
            return $this->avatarColor;
        }

        public function setAvatarColor(string $avatarColor): void
        {
            $this->avatarColor = $avatarColor;
        }

        public function getIban(): string
        {
            return $this->iban;
        }

        public function setIban(string $iban): void
        {
            $this->iban = $iban;
        }
    }


Impact
======

A various set of additional validators can now be used within Extbase. This
allows for an easier integration of validations without the need of building
custom validators, since Symfony already ships with a huge amount of available
validators.

..  index:: Frontend, ext:extbase
