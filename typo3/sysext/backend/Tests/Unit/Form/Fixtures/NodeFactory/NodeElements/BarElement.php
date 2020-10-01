<?php

declare(strict_types=1);

namespace TYPO3\CMS\Backend\Tests\Unit\Form\Fixtures\NodeFactory\NodeElements;

use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\NodeInterface;

class BarElement implements NodeInterface
{
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
    }

    public function render()
    {
        // TODO: Implement render() method.
    }
}
