..  include:: /Includes.rst.txt

..  _feature-106945-1750757664:

===============================================================
Feature: #106945 - Allow usage of Symfony validators in Extbase
===============================================================

See :issue:`106945`

Description
===========

Extbase models and controllers now support the use of
`Symfony Validators <https://symfony.com/doc/current/components/validator.html>`__.
Validators are based on Symfony *Constraints*, which can be added as attributes
to domain model properties and controller methods.

Once a constraint attribute is detected while reflecting properties or methods,
it is decorated by the new
:php-short:`\TYPO3\CMS\Extbase\Validation\Validator\ConstraintDecoratingValidator`
class, which is compatible with Extbase's
:php-short:`\TYPO3\CMS\Extbase\Validation\ValidatorInterface`.

Decorated constraints may include localizable messages. If a message contains
valid `LLL:` syntax, the label will be translated automatically. The decorating
validator also handles message parameters by converting named parameters such as
`{{ value }}` into :php:`sprintf`-compatible placeholders like `%1$s`.

..  important::

    Most available Symfony constraints, such as :php:`#[NotBlank]` and
    :php:`#[Regex]`, can be used. However, more complex constraints such as
    :php:`#[File]` or :php:`#[Image]` are not yet compatible with the current
    Extbase implementation, as they are closely tied to the Symfony framework.
    Compatibility for those constraints may be added in the future.

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

A wide range of Symfony validators can now be used directly in Extbase.
This provides a more flexible and standardized validation workflow without the
need to implement custom validators, as Symfony already ships with a large
number of predefined constraints.

..  index:: Frontend, ext:extbase
