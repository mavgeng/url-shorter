<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Click;
use App\Entity\Url;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UrlControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        $this->entityManager->getConnection()->executeStatement('DELETE FROM clicks');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM urls');
    }

    public function testHomePageLoads(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testStoreCreatesUrlInDatabase(): void
    {
        $this->client->request('POST', '/url', ['url' => 'https://example.com']);

        $url = $this->entityManager->getRepository(Url::class)->findOneBy(['original' => 'https://example.com']);

        $this->assertNotNull($url);
        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]{6}$/', $url->getShortCode());
        $this->assertNull($url->getExpiresAt());
    }

    public function testStoreWithTtlSetsExpiry(): void
    {
        $this->client->request('POST', '/url', ['url' => 'https://example.com', 'ttl' => 7]);

        $url = $this->entityManager->getRepository(Url::class)->findOneBy(['original' => 'https://example.com']);

        $this->assertNotNull($url);
        $this->assertNotNull($url->getExpiresAt());
        $this->assertGreaterThan(new \DateTimeImmutable('+6 days'), $url->getExpiresAt());
    }

    public function testStoreWithBlankUrlShowsError(): void
    {
        $this->client->request('POST', '/url', ['url' => '']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'URL is required');
    }

    public function testStoreWithInvalidUrlShowsError(): void
    {
        $this->client->request('POST', '/url', ['url' => 'not-a-url']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Invalid URL');
    }

    public function testRedirectReturns302ToOriginalUrl(): void
    {
        $url = $this->createUrl('https://example.com');

        $this->client->request('GET', '/'.$url->getShortCode());

        $this->assertResponseRedirects('https://example.com', Response::HTTP_FOUND);
    }

    public function testRedirectSavesClickWithMetadata(): void
    {
        $url = $this->createUrl('https://example.com');

        $this->client->request('GET', '/'.$url->getShortCode(), server: [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36',
            'HTTP_REFERER' => 'https://referrer.com',
        ]);

        $this->entityManager->clear();
        $clicks = $this->entityManager->getRepository(Click::class)->findBy(['url' => $url]);
        $this->assertCount(1, $clicks);

        /** @var Click $click */
        $click = current($clicks);
        $this->assertSame('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', $click->getUserAgent());
        $this->assertSame('Chrome', $click->getBrowser());
        $this->assertSame('desktop', $click->getDevice());
    }

    public function testRedirectWithUnknownCodeReturns404(): void
    {
        $this->client->request('GET', '/xxxxxx');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testRedirectWithExpiredUrlReturns410(): void
    {
        $url = $this->createUrl('https://example.com', expiresAt: new \DateTimeImmutable('-1 day'));

        $this->client->request('GET', '/'.$url->getShortCode());

        $this->assertResponseStatusCodeSame(Response::HTTP_GONE);
    }

    public function testRedirectWithExpiredUrlDoesNotSaveClick(): void
    {
        $url = $this->createUrl('https://example.com', expiresAt: new \DateTimeImmutable('-1 day'));

        $this->client->request('GET', '/'.$url->getShortCode());

        $clicks = $this->entityManager->getRepository(Click::class)->findBy(['url' => $url]);
        $this->assertCount(0, $clicks);
    }

    private function createUrl(string $original, ?\DateTimeImmutable $expiresAt = null): Url
    {
        $url = new Url();
        $url->setOriginal($original);
        $url->setShortCode($this->generateUniqueCode());

        if (null !== $expiresAt) {
            $url->setExpiresAt($expiresAt);
        }

        $this->entityManager->persist($url);
        $this->entityManager->flush();

        return $url;
    }

    private function generateUniqueCode(): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6);
    }
}
