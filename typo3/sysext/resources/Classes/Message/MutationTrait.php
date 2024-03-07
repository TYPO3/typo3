<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Message;

trait MutationTrait
{

    protected function mutate(array $mutations): static
    {
        return new static(...\array_replace(\get_object_vars($this), $mutations));
    }
}
