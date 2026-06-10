<?php

namespace App\Service;

use App\Repository\UrlRepository;
use Symfony\Component\String\ByteString;

class CodeGenerator
{
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const LENGTH = 6;
    private const MAX_ATTEMPTS = 10;

    public function __construct(
        private readonly UrlRepository $urlRepository,
    ) {
    }

    public function generate(): string
    {
        for ($i = 0; $i < self::MAX_ATTEMPTS; ++$i) {
            $code = ByteString::fromRandom(self::LENGTH, self::ALPHABET)->toString();

            if (null === $this->urlRepository->findByShortCode($code)) {
                return $code;
            }
        }

        throw new \RuntimeException('Failed to generate a unique code, please try again');
    }
}
