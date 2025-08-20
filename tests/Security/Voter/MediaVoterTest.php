<?php

namespace Security\Voter;

use App\Entity\Media;
use App\Entity\User;
use App\Security\Voter\MediaVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use stdClass;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class MediaVoterTest extends TestCase
{
    private MockObject|AccessDecisionManagerInterface $adm;
    private MediaVoter $voter;
    private MockObject|TokenInterface $token;
    private Media $media;
    private User $user;

    /**
     * @throws ReflectionException
     */
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

    /**
     * @throws ReflectionException
     */
    private function setEntityId(object $entity, int $id): void
    {
        $reflection = new ReflectionClass($entity);
        $property = $reflection->getProperty('id');
        $property->setValue($entity, $id);
    }

    /**
     * @throws ReflectionException
     */
    private function callVoteOnAttribute(string $attribute, $subject, TokenInterface $token = null): bool
    {
        $ref = new ReflectionClass($this->voter);
        return $ref->getMethod('voteOnAttribute')->invoke($this->voter, $attribute, $subject, $token ?? $this->token);
    }

    /**
     * @throws ReflectionException
     */
    private function callSupports(string $attribute, $subject): bool
    {
        $ref = new ReflectionClass($this->voter);
        return $ref->getMethod('supports')->invoke($this->voter, $attribute, $subject);
    }

    /**
     * @throws ReflectionException
     */
    public function testSupportsReturnsFalseForUnsupportedAttribute(): void
    {
        $this->assertFalse($this->callSupports('unsupported', $this->media));
    }

    /**
     * @throws ReflectionException
     */
    public function testSupportsReturnsFalseForNonMediaSubject(): void
    {
        $this->assertFalse($this->callSupports(MediaVoter::VIEW, new stdClass()));
    }

    /**
     * @throws ReflectionException
     */
    public function testSupportsReturnsTrueForSupportedAttributeAndMedia(): void
    {
        $this->assertTrue($this->callSupports(MediaVoter::VIEW, $this->media));
    }

    /**
     * @throws ReflectionException
     */
    public function testVoteOnAttributeReturnsFalseIfUserNotConnected(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->assertFalse($this->callVoteOnAttribute(MediaVoter::VIEW, $this->media, $token));
    }

    /**
     * @throws ReflectionException
     */
    public function testVoteOnAttributeView(): void
    {
        $this->assertTrue($this->callVoteOnAttribute(MediaVoter::VIEW, $this->media));
    }

    /**
     * @throws ReflectionException
     */
    public function testVoteOnAttributeEditAsAdmin(): void
    {
        $this->adm->method('decide')->willReturn(true);
        $this->assertTrue($this->callVoteOnAttribute(MediaVoter::EDIT, $this->media));
    }

    /**
     * @throws ReflectionException
     */
    public function testVoteOnAttributeEditAsAuthor(): void
    {
        $this->adm->method('decide')->willReturn(false);
        $this->media->setUser($this->user);
        $this->assertTrue($this->callVoteOnAttribute(MediaVoter::EDIT, $this->media));
    }

    /**
     * @throws ReflectionException
     */
    public function testVoteOnAttributeEditAsOther(): void
    {
        $this->adm->method('decide')->willReturn(false);
        $otherUser = new User();
        $this->setEntityId($otherUser, 2);
        $this->media->setUser($otherUser);
        $this->assertFalse($this->callVoteOnAttribute(MediaVoter::EDIT, $this->media));
    }

    /**
     * @throws ReflectionException
     */
    public function testVoteOnAttributeDeleteAsAdmin(): void
    {
        $this->adm->method('decide')->willReturn(true);
        $this->assertTrue($this->callVoteOnAttribute(MediaVoter::DELETE, $this->media));
    }

    /**
     * @throws ReflectionException
     */
    public function testVoteOnAttributeDeleteAsAuthor(): void
    {
        $this->adm->method('decide')->willReturn(false);
        $this->media->setUser($this->user);
        $this->assertTrue($this->callVoteOnAttribute(MediaVoter::DELETE, $this->media));
    }

    /**
     * @throws ReflectionException
     */
    public function testVoteOnAttributeDeleteAsOther(): void
    {
        $this->adm->method('decide')->willReturn(false);
        $otherUser = new User();
        $this->setEntityId($otherUser, 2);
        $this->media->setUser($otherUser);
        $this->assertFalse($this->callVoteOnAttribute(MediaVoter::DELETE, $this->media));
    }

    /**
     * @throws ReflectionException
     */
    public function testVoteOnAttributeAddAsAllowedUser(): void
    {
        $this->adm->method('decide')->willReturn(true);
        $this->assertTrue($this->callVoteOnAttribute(MediaVoter::ADD, $this->media));
    }

    /**
     * @throws ReflectionException
     */
    public function testVoteOnAttributeAddAsNotAllowedUser(): void
    {
        $this->adm->method('decide')->willReturn(false);
        $this->assertFalse($this->callVoteOnAttribute(MediaVoter::ADD, $this->media));
    }
}
