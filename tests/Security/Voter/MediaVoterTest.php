<?php

namespace App\Tests\Security\Voter;

use App\Entity\Media;
use App\Entity\User;
use App\Security\Voter\MediaVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class MediaVoterTest extends TestCase
{
    private MockObject|AccessDecisionManagerInterface $adm;
    private MediaVoter $voter;
    private MockObject|TokenInterface $token;
    private Media $media;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adm = $this->createMock(AccessDecisionManagerInterface::class);
        $this->voter = new MediaVoter($this->adm);
        $this->media = new Media();
        $this->user = new User();
        $this->setEntityId($this->user, 1);

        $this->token = $this->createMock(TokenInterface::class);
        $this->token->method('getUser')->willReturn($this->user);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new \ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }

    private function callVoteOnAttribute(string $attribute, $subject, TokenInterface $token = null): bool
    {
        $ref = new \ReflectionClass($this->voter);
        $method = $ref->getMethod('voteOnAttribute');
        $method->setAccessible(true);

        return $method->invoke($this->voter, $attribute, $subject, $token ?? $this->token);
    }

    private function callSupports(string $attribute, $subject): bool
    {
        $ref = new \ReflectionClass($this->voter);
        $method = $ref->getMethod('supports');
        $method->setAccessible(true);

        return $method->invoke($this->voter, $attribute, $subject);
    }

    public function testSupportsReturnsFalseForUnsupportedAttribute(): void
    {
        $this->assertFalse($this->callSupports('unsupported', $this->media));
    }

    public function testSupportsReturnsFalseForNonMediaSubject(): void
    {
        $this->assertFalse($this->callSupports(MediaVoter::VIEW, new \stdClass()));
    }

    public function testSupportsReturnsTrueForSupportedAttributeAndMedia(): void
    {
        $this->assertTrue($this->callSupports(MediaVoter::VIEW, $this->media));
    }

    public function testVoteOnAttributeReturnsFalseIfUserNotConnected(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->assertFalse($this->callVoteOnAttribute(MediaVoter::VIEW, $this->media, $token));
    }

    public function testVoteOnAttributeView(): void
    {
        $this->assertTrue($this->callVoteOnAttribute(MediaVoter::VIEW, $this->media));
    }

    public function testVoteOnAttributeEditAsAdmin(): void
    {
        $this->adm->method('decide')->willReturn(true);
        $this->assertTrue($this->callVoteOnAttribute(MediaVoter::EDIT, $this->media));
    }

    public function testVoteOnAttributeEditAsAuthor(): void
    {
        $this->adm->method('decide')->willReturn(false);
        $this->media->setUser($this->user);
        $this->assertTrue($this->callVoteOnAttribute(MediaVoter::EDIT, $this->media));
    }

    public function testVoteOnAttributeEditAsOther(): void
    {
        $this->adm->method('decide')->willReturn(false);
        $otherUser = new User();
        $this->setEntityId($otherUser, 2);
        $this->media->setUser($otherUser);
        $this->assertFalse($this->callVoteOnAttribute(MediaVoter::EDIT, $this->media));
    }

    public function testVoteOnAttributeDeleteAsAdmin(): void
    {
        $this->adm->method('decide')->willReturn(true);
        $this->assertTrue($this->callVoteOnAttribute(MediaVoter::DELETE, $this->media));
    }

    public function testVoteOnAttributeDeleteAsAuthor(): void
    {
        $this->adm->method('decide')->willReturn(false);
        $this->media->setUser($this->user);
        $this->assertTrue($this->callVoteOnAttribute(MediaVoter::DELETE, $this->media));
    }

    public function testVoteOnAttributeDeleteAsOther(): void
    {
        $this->adm->method('decide')->willReturn(false);
        $otherUser = new User();
        $this->setEntityId($otherUser, 2);
        $this->media->setUser($otherUser);
        $this->assertFalse($this->callVoteOnAttribute(MediaVoter::DELETE, $this->media));
    }

    public function testVoteOnAttributeAddAsAllowedUser(): void
    {
        $this->adm->method('decide')->willReturn(true);
        $this->assertTrue($this->callVoteOnAttribute(MediaVoter::ADD, $this->media));
    }

    public function testVoteOnAttributeAddAsNotAllowedUser(): void
    {
        $this->adm->method('decide')->willReturn(false);
        $this->assertFalse($this->callVoteOnAttribute(MediaVoter::ADD, $this->media));
    }
}
