<?php

namespace App\Security\Voter;

use App\Entity\Media;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @phpstan-type Attribute string
 * @extends Voter<Attribute, Media>
 */
class MediaVoter extends Voter
{
    public function __construct(
        private readonly AccessDecisionManagerInterface $accessDecisionManager
    ) {
    }

    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const ADD = 'add';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::ADD])) {
            return false;
        }
        return $subject instanceof Media;
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
            self::EDIT => $this->canEdit($subject, $user, $vote ?: null, $token),
            self::DELETE => $this->canDelete($subject, $user, $vote ?: null, $token),
            self::ADD => $this->canAdd($token, $vote ?: null),
            default => false,
        };
    }

    private function canView(): bool
    {
        return true;
    }
    private function canEdit(Media $subject, User $user, ?Vote $vote, TokenInterface $token): bool
    {
        if (!$this->isAuthorOrAdmin($subject, $user, $token)) {
            $vote?->addReason('Seulement l\'auteur à accès à cette ressource');
            return false;
        }
        return true;
    }

    private function canDelete(Media $subject, User $user, ?Vote $vote, TokenInterface $token): bool
    {
        return $this->canEdit($subject, $user, $vote, $token);
    }

    private function isAuthorOrAdmin(Media $subject, User $user, TokenInterface $token): bool
    {
        return $this->accessDecisionManager->decide($token, [User::ADMIN_ROLE])
            || ($subject->getUser()?->getId() === $user->getId());
    }

    private function canAdd(TokenInterface $token, ?Vote $vote): bool
    {
        if (!$this->accessDecisionManager->decide($token, [User::GUEST_ROLE, User::ADMIN_ROLE])) {
            $vote?->addReason('Seul les invités peuvent ajouter des médias.');
            return false;
        }

        return true;
    }
}
