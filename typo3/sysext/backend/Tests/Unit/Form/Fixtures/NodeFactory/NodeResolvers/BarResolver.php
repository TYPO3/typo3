<?php

declare(strict_types=1);

namespace TYPO3\CMS\Backend\Tests\Unit\Form\Fixtures\NodeFactory\NodeResolvers;

use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\NodeResolverInterface;
use TYPO3\CMS\Backend\Tests\Unit\Form\Fixtures\NodeFactory\NodeElements\BarElement;

class BarResolver implements NodeResolverInterface
{
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
    }

    public function resolve(): string
    {
        return BarElement::class;
    }
}
