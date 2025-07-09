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
    private MockObject|AccessDecisionManagerInterface $adm;
    private AlbumVoter $voter;
    private MockObject|TokenInterface $token;
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

    public function testVoteOnAttributeEditAsAdmin(): void
    {
        $this->adm->method('decide')->willReturn(true);

        $this->assertTrue($this->callVoteOnAttribute(AlbumVoter::EDIT, $this->album));
    }

    public function testVoteOnAttributeEditAsNonAdmin(): void
    {
        $this->adm->method('decide')->willReturn(false);

        $this->assertFalse($this->callVoteOnAttribute(AlbumVoter::EDIT, $this->album));
    }

    public function testSupportsReturnsFalseForUnsupportedAttribute(): void
    {
        $this->assertFalse($this->callSupports('unsupported', $this->album));
    }

    public function testSupportsReturnsFalseForNonAlbumSubject(): void
    {
        $this->assertFalse($this->callSupports(AlbumVoter::VIEW, new \stdClass()));
    }

    public function testSupportsReturnsTrueForSupportedAttributeAndAlbum(): void
    {
        $this->assertTrue($this->callSupports(AlbumVoter::VIEW, $this->album));
    }

    public function testVoteOnAttributeReturnsFalseIfUserNotConnected(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->assertFalse($this->callVoteOnAttribute(AlbumVoter::VIEW, $this->album, $token));
    }

    public function testVoteOnAttributeView(): void
    {
        $this->assertTrue($this->callVoteOnAttribute(AlbumVoter::VIEW, $this->album));
    }

    public function testVoteOnAttributeDeleteAsAdmin(): void
    {
        $this->adm->method('decide')->willReturn(true);

        $this->assertTrue($this->callVoteOnAttribute(AlbumVoter::DELETE, $this->album));
    }

    public function testVoteOnAttributeDeleteAsNonAdmin(): void
    {
        $this->adm->method('decide')->willReturn(false);

        $this->assertFalse($this->callVoteOnAttribute(AlbumVoter::DELETE, $this->album));
    }
}
