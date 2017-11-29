<?php
declare(strict_types=1);

namespace TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures;

use TYPO3\CMS\Extbase\Annotation\Inject;

class PublicPropertyInjectClass
{
    /**
     * @Inject
     * @var \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClassForPublicPropertyInjection
     */
    public $foo;
}

class ArgumentTestClassForPublicPropertyInjection
{
}

class ProtectedPropertyInjectClass
{

    /**
     * @Inject
     * @var \TYPO3\CMS\Extbase\Tests\Unit\Object\Container\Fixtures\ArgumentTestClassForPublicPropertyInjection
     */
    protected $foo;

    public function getFoo()
    {
        return $this->foo;
    }
}
