<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Url;
use App\Repository\UrlRepository;
use App\Service\CodeGenerator;
use PHPUnit\Framework\TestCase;

class CodeGeneratorTest extends TestCase
{
    public function testGeneratesCodeWithCorrectFormat(): void
    {
        $repository = $this->createMock(UrlRepository::class);
        $repository->method('findByShortCode')->willReturn(null);

        $code = new CodeGenerator($repository)->generate();

        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]{6}$/', $code);
    }

    public function testThrowsExceptionWhenAllAttempts(): void
    {
        $repository = $this->createMock(UrlRepository::class);
        $repository->method('findByShortCode')->willReturn(new Url());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Failed to generate a unique code');

        new CodeGenerator($repository)->generate();
    }

    public function testRetriesOnCollisionAndSucceeds(): void
    {
        $repository = $this->createMock(UrlRepository::class);
        $repository->expects($this->exactly(3))
            ->method('findByShortCode')
            ->willReturnOnConsecutiveCalls(new Url(), new Url(), null);

        $code = new CodeGenerator($repository)->generate();

        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]{6}$/', $code);
    }

    public function testMakesExactlyMaxAttemptsBeforeFailing(): void
    {
        $repository = $this->createMock(UrlRepository::class);
        $repository->expects($this->exactly(10))
            ->method('findByShortCode')
            ->willReturn(new Url());

        $this->expectException(\RuntimeException::class);

        new CodeGenerator($repository)->generate();
    }
}
