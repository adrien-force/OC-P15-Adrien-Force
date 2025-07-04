<?php

namespace App\Security\Voter;

use App\Entity\Album;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @phpstan-type Attribute string
 * @extends Voter<Attribute, Album>
 */
class AlbumVoter extends Voter
{
    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager
    ) {
    }

    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }
        return $subject instanceof Album;
    }
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            $vote?->addReason('Vous devez être connecté pour accèder à cette ressource.');
            return false;
        }

        return match ($attribute) {
            self::VIEW => $this->canView(),
            self::EDIT => $this->canEdit($token, $vote ?: null),
            self::DELETE => $this->canDelete($token, $vote ?: null),
            default => false,
        };
    }

    private function canView(): bool
    {
        return true;
    }
    private function canEdit(TokenInterface $token, Vote $vote = null): bool
    {
        if (!($this->accessDecisionManager->decide($token, [User::ADMIN_ROLE]))) {
            $vote?->addReason('Seul un administrateur est autorisé à modifier cette ressource.');
            return false;
        }

        return true;
    }
    private function canDelete(TokenInterface $token, Vote $vote = null): bool
    {
        return $this->canEdit($token, $vote ?: null);
    }
}
