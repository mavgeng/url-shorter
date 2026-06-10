<?php

declare(strict_types=1);

namespace App\Tests\Repository;

use App\Entity\Click;
use App\Entity\Url;
use App\Repository\ClickRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ClickRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ClickRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->em->getRepository(Click::class);

        $this->em->getConnection()->executeStatement('DELETE FROM clicks');
        $this->em->getConnection()->executeStatement('DELETE FROM urls');
    }

    public function testCountByUrlReturnsZeroWithNoClicks(): void
    {
        $url = $this->createUrl();

        $this->assertSame(0, $this->repository->countByUrl($url));
    }

    public function testCountByUrlReturnsCorrectTotal(): void
    {
        $url = $this->createUrl();
        $this->createClick($url);
        $this->createClick($url);
        $this->createClick($url);

        $this->assertSame(3, $this->repository->countByUrl($url));
    }

    public function testCountByUrlIsIsolatedPerUrl(): void
    {
        $url1 = $this->createUrl();
        $url2 = $this->createUrl();
        $this->createClick($url1);
        $this->createClick($url1);

        $this->assertSame(2, $this->repository->countByUrl($url1));
        $this->assertSame(0, $this->repository->countByUrl($url2));
    }

    public function testCountUniqueByUrlReturnsZeroWithNoClicks(): void
    {
        $url = $this->createUrl();

        $this->assertSame(0, $this->repository->countUniqueByUrl($url));
    }

    public function testCountUniqueByUrlCountsDistinctIps(): void
    {
        $url = $this->createUrl();
        $this->createClick($url);
        $this->createClick($url, ip: '2.2.2.2');
        $this->createClick($url, ip: '2.2.2.2');

        $this->assertSame(2, $this->repository->countUniqueByUrl($url));
    }

    public function testGroupedByDayReturnsEmptyArrayWithNoClicks(): void
    {
        $url = $this->createUrl();

        $this->assertSame([], $this->repository->countByUrlGroupedByDay($url));
    }

    public function testGroupedByDayGroupsCorrectly(): void
    {
        $url = $this->createUrl();
        $this->createClick($url);
        $this->createClick($url);

        $result = $this->repository->countByUrlGroupedByDay($url);

        $this->assertCount(1, $result);
        $this->assertSame(2, $result[0]['count']);
        $this->assertSame(date('Y-m-d'), $result[0]['day']);
    }

    public function testGroupedByRefererReturnsEmptyWithNoClicks(): void
    {
        $url = $this->createUrl();

        $this->assertSame([], $this->repository->countByUrlGroupedByReferer($url));
    }

    public function testGroupedByRefererUseNullAsDirect(): void
    {
        $url = $this->createUrl();
        $this->createClick($url);

        $result = $this->repository->countByUrlGroupedByReferer($url);

        $this->assertCount(1, $result);
        $this->assertSame('Direct', $result[0]['referer']);
    }

    public function testGroupedByRefererGroups(): void
    {
        $url = $this->createUrl();
        $this->createClick($url, referer: 'https://google.com');
        $this->createClick($url, referer: 'https://twitter.com');
        $this->createClick($url, referer: 'https://google.com');

        $result = $this->repository->countByUrlGroupedByReferer($url);

        $this->assertCount(2, $result);
        $this->assertSame('https://google.com', $result[0]['referer']);
        $this->assertSame(2, $result[0]['count']);
        $this->assertSame('https://twitter.com', $result[1]['referer']);
        $this->assertSame(1, $result[1]['count']);
    }

    public function testGroupedByBrowserLabelsNullAsUnknown(): void
    {
        $url = $this->createUrl();
        $this->createClick($url);

        $result = $this->repository->countByUrlGroupedByBrowser($url);

        $this->assertCount(1, $result);
        $this->assertSame('Unknown', $result[0]['browser']);
    }

    public function testGroupedByBrowserGroups(): void
    {
        $url = $this->createUrl();
        $this->createClick($url, browser: 'Chrome');
        $this->createClick($url, browser: 'Chrome');
        $this->createClick($url, browser: 'Firefox');

        $result = $this->repository->countByUrlGroupedByBrowser($url);

        $this->assertCount(2, $result);
        $this->assertSame('Chrome', $result[0]['browser']);
        $this->assertSame(2, $result[0]['count']);
        $this->assertSame('Firefox', $result[1]['browser']);
        $this->assertSame(1, $result[1]['count']);
    }

    public function testGroupedByDeviceLabelsNullAsUnknown(): void
    {
        $url = $this->createUrl();
        $this->createClick($url);

        $result = $this->repository->countByUrlGroupedByDevice($url);

        $this->assertCount(1, $result);
        $this->assertSame('Unknown', $result[0]['device']);
    }

    public function testGroupedByDeviceGroupsCorrectly(): void
    {
        $url = $this->createUrl();
        $this->createClick($url, device: 'desktop');
        $this->createClick($url, device: 'mobile');
        $this->createClick($url, device: 'desktop');

        $result = $this->repository->countByUrlGroupedByDevice($url);

        $this->assertCount(2, $result);
        $this->assertSame('desktop', $result[0]['device']);
        $this->assertSame(2, $result[0]['count']);
    }

    private function createUrl(): Url
    {
        $url = new Url();
        $url->setOriginal('https://example.com');
        $url->setShortCode(substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 6));

        $this->em->persist($url);
        $this->em->flush();

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

        $this->em->persist($click);
        $this->em->flush();

        return $click;
    }
}
