<?php

namespace App\Tests\Security\Voter;

use App\Entity\Album;
use App\Entity\User;
use App\Security\Voter\AlbumVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AlbumVoterTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $adm;
    private AlbumVoter $voter;
    private \PHPUnit\Framework\MockObject\MockObject $token;
    private Album $album;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adm = $this->createMock(AccessDecisionManagerInterface::class);
        $this->voter = new AlbumVoter($this->adm);
        $this->album = new Album();

        $user = new User();
        $this->token = $this->createMock(TokenInterface::class);
        $this->token->method('getUser')->willReturn($user);
    }

    /**
     * @throws \ReflectionException
     */
    private function callVoteOnAttribute(string $attribute, \App\Entity\Album $subject, ?TokenInterface $token = null): bool
    {
        $ref = new \ReflectionClass($this->voter);

        return $ref->getMethod('voteOnAttribute')->invoke($this->voter, $attribute, $subject, $token ?? $this->token);
    }

    /**
     * @throws \ReflectionException
     */
    private function callSupports(string $attribute, \App\Entity\Album|\stdClass $subject): bool
    {
        $ref = new \ReflectionClass($this->voter);

        return $ref->getMethod('supports')->invoke($this->voter, $attribute, $subject);
    }

    /**
     * @throws \ReflectionException
     */
    public function testVoteOnAttributeEditAsAdmin(): void
    {
        $this->adm->method('decide')->willReturn(true);

        $this->assertTrue($this->callVoteOnAttribute(AlbumVoter::EDIT, $this->album));
    }

    /**
     * @throws \ReflectionException
     */
    public function testVoteOnAttributeEditAsNonAdmin(): void
    {
        $this->adm->method('decide')->willReturn(false);

        $this->assertFalse($this->callVoteOnAttribute(AlbumVoter::EDIT, $this->album));
    }

    /**
     * @throws \ReflectionException
     */
    public function testSupportsReturnsFalseForUnsupportedAttribute(): void
    {
        $this->assertFalse($this->callSupports('unsupported', $this->album));
    }

    /**
     * @throws \ReflectionException
     */
    public function testSupportsReturnsFalseForNonAlbumSubject(): void
    {
        $this->assertFalse($this->callSupports(AlbumVoter::VIEW, new \stdClass()));
    }

    /**
     * @throws \ReflectionException
     */
    public function testSupportsReturnsTrueForSupportedAttributeAndAlbum(): void
    {
        $this->assertTrue($this->callSupports(AlbumVoter::VIEW, $this->album));
    }

    /**
     * @throws \ReflectionException
     */
    public function testVoteOnAttributeReturnsFalseIfUserNotConnected(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->assertFalse($this->callVoteOnAttribute(AlbumVoter::VIEW, $this->album, $token));
    }

    /**
     * @throws \ReflectionException
     */
    public function testVoteOnAttributeView(): void
    {
        $this->assertTrue($this->callVoteOnAttribute(AlbumVoter::VIEW, $this->album));
    }

    /**
     * @throws \ReflectionException
     */
    public function testVoteOnAttributeDeleteAsAdmin(): void
    {
        $this->adm->method('decide')->willReturn(true);

        $this->assertTrue($this->callVoteOnAttribute(AlbumVoter::DELETE, $this->album));
    }

    /**
     * @throws \ReflectionException
     */
    public function testVoteOnAttributeDeleteAsNonAdmin(): void
    {
        $this->adm->method('decide')->willReturn(false);

        $this->assertFalse($this->callVoteOnAttribute(AlbumVoter::DELETE, $this->album));
    }
}
