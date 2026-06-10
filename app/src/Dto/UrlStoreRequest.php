<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UrlStoreRequest
{
    #[Assert\NotBlank(message: 'URL is required')]
    #[Assert\Url(message: 'Invalid URL', requireTld: true)]
    #[Assert\Length(max: 2048, maxMessage: 'URL is too long')]
    public string $url = '';

    #[Assert\NotNull(message: 'Expiration is invalid')]
    #[Assert\Range(
        notInRangeMessage: 'Expiration must be between {{ min }} and {{ max }} days',
        min: 0,
        max: 3650,
    )]
    public int $ttl = 0;
}
