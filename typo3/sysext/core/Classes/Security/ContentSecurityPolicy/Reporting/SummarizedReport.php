<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting;

/**
 * @internal
 */
class SummarizedReport extends Report
{
    protected int $count = 0;
    /**
     * @var list<ReportAttribute>
     */
    protected array $attributes = [];

    /**
     * @var list<string>
     */
    protected array $mutationHashes = [];

    public function withCount(int $count): self
    {
        $target = clone $this;
        $target->count = $count;
        return $target;
    }

    public function withAttribute(ReportAttribute $attribute): self
    {
        if (in_array($attribute, $this->attributes, true)) {
            return $this;
        }
        $target = clone $this;
        $target->attributes[] = $attribute;
        return $target;
    }

    public function withMutationHashes(string ...$mutationHashes): self
    {
        if ($this->mutationHashes === $mutationHashes) {
            return $this;
        }
        $target = clone $this;
        $target->mutationHashes = $mutationHashes;
        return $target;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();
        $data['count'] = $this->count;
        $data['attributes'] = $this->attributes;
        $data['mutationHashes'] = $this->mutationHashes;
        return $data;
    }
}
