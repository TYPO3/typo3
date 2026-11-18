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

namespace TYPO3\CMS\Core\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use TYPO3\CMS\Core\Attribute\AsSecurityVoter;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\Authorization\Policy\PolicyDecisionPointInterface;
use TYPO3\CMS\Core\Security\Authorization\Policy\ResourceIdentifier;
use TYPO3\CMS\Core\Security\Authorization\Policy\Scope;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Decides on editing records in a language, given as language of a site,
 * isGranted(Scope::WRITE, $site->getLanguageById(1)), or as language id
 * without a site: isGranted(Scope::WRITE, ResourceIdentifier::language(1)).
 * Both are decided alike, as grants name language ids, not sites; a voter
 * restricting a language supports both.
 *
 * @extends PrincipalVoter<string, SiteLanguage|ResourceIdentifier>
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
#[AsSecurityVoter]
final class LanguageVoter extends PrincipalVoter
{
    public function __construct(
        private readonly PolicyDecisionPointInterface $policyDecisionPoint,
    ) {}

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === Scope::WRITE;
    }

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, SiteLanguage::class, true)
            || is_a($subjectType, ResourceIdentifier::class, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Scope::WRITE
            && ($subject instanceof SiteLanguage
                || ($subject instanceof ResourceIdentifier && $subject->type === ResourceIdentifier::LANGUAGE));
    }

    protected function voteOnPrincipal(string $attribute, mixed $subject, Principal $principal, ?Vote $vote = null): bool
    {
        $resource = $subject instanceof SiteLanguage ? ResourceIdentifier::language($subject->getLanguageId()) : $subject;
        return $this->policyDecisionPoint->decide($principal, $attribute, $resource);
    }
}
