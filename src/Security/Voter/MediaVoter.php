<?php

namespace App\Security\Voter;

use App\Entity\Media;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @phpstan-type Attribute string
 * @extends Voter<Attribute, Media>
 */
class MediaVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
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
            self::EDIT => $this->canEdit($subject, $user, $vote ?: null),
            self::DELETE => $this->canDelete($subject, $user, $vote ?: null),
            default => false,
        };
    }

    private function canView(): bool
    {
        return true;
    }
    private function canEdit(Media $subject, User $user, Vote $vote = null): bool
    {
        if (!$this->isAuthor($subject, $user)) {
            $vote?->addReason('Seulement l\'auteur à accès à cette ressource');
            return false;
        }
        return true;
    }

    private function canDelete(Media $subject, User $user, Vote $vote = null): bool
    {
        return $this->canEdit($subject, $user, $vote);
    }

    private function isAuthor(Media $subject, User $user): bool
    {
        return $user->isAdmin() || ($subject->getUser()?->getId() === $user->getId());
    }
}