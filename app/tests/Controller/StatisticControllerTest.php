<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Click;
use App\Entity\Url;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class StatisticControllerTest extends WebTestCase
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

    public function testShowPageLoads(): void
    {
        $url = $this->createUrl('https://example.com');

        $this->client->request('GET', '/statistic/'.$url->getShortCode());

        $this->assertResponseIsSuccessful();
    }

    public function testShowWithUnknownCodeReturns404(): void
    {
        $this->client->request('GET', '/statistic/xxxxxx');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testShowDisplaysOriginalUrlWithShortLink(): void
    {
        $url = $this->createUrl('https://example.com');

        $this->client->request('GET', '/statistic/'.$url->getShortCode());

        $this->assertSelectorTextContains('body', 'https://example.com');

        $this->assertSelectorExists('input[value*="/'.$url->getShortCode().'"]');
    }

    public function testShowDisplaysZeroCountsWithNoClicks(): void
    {
        $url = $this->createUrl('https://example.com');

        $crawler = $this->client->request('GET', '/statistic/'.$url->getShortCode());

        $counts = $crawler->filter('.display-4')->each(fn ($node) => (int) $node->text());
        $this->assertSame([0, 0], $counts);
    }

    public function testShowDisplaysTotalClickCount(): void
    {
        $url = $this->createUrl('https://example.com');
        $this->createClick($url);
        $this->createClick($url, ip: '2.2.2.2');
        $this->createClick($url, ip: '2.2.2.2');

        $crawler = $this->client->request('GET', '/statistic/'.$url->getShortCode());

        $total = (int) $crawler->filter('.display-4')->first()->text();
        $this->assertSame(3, $total);
    }

    public function testShowDisplaysUniqueClickCount(): void
    {
        $url = $this->createUrl('https://example.com');
        $this->createClick($url);
        $this->createClick($url, ip: '2.2.2.2');
        $this->createClick($url, ip: '2.2.2.2');

        $crawler = $this->client->request('GET', '/statistic/'.$url->getShortCode());

        $unique = (int) $crawler->filter('.display-4')->eq(1)->text();
        $this->assertSame(2, $unique);
    }

    public function testShowClicksByDayChartRenderedWhenClicksExist(): void
    {
        $url = $this->createUrl('https://example.com');
        $this->createClick($url);

        $this->client->request('GET', '/statistic/'.$url->getShortCode());

        $this->assertSelectorExists('#clicksChart');
    }

    public function testShowClicksByDayChartNotRenderedWithNoClicks(): void
    {
        $url = $this->createUrl('https://example.com');

        $this->client->request('GET', '/statistic/'.$url->getShortCode());

        $this->assertSelectorNotExists('#clicksChart');
    }

    private function createUrl(string $original, ?\DateTimeImmutable $expiresAt = null): Url
    {
        $url = new Url();
        $url->setOriginal($original);
        $url->setShortCode(substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6));

        if (null !== $expiresAt) {
            $url->setExpiresAt($expiresAt);
        }

        $this->entityManager->persist($url);
        $this->entityManager->flush();

        return $url;
    }

    private function createClick(
        Url $url,
        ?string $ip = '1.1.1.1',
        ?string $referer = null,
        ?string $browser = null,
        ?string $device = null,
    ): Click {
        $click = new Click($url);
        $click->setIp($ip)
            ->setReferer($referer)
            ->setBrowser($browser)
            ->setDevice($device);

        $this->entityManager->persist($click);
        $this->entityManager->flush();

        return $click;
    }
}
