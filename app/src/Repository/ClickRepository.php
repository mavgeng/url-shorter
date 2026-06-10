<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Click;
use App\Entity\Url;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends ServiceEntityRepository<Click>
 */
class ClickRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Click::class);
    }

    public function countByUrl(Url $url): int
    {
        return $this->count(['url' => $url]);
    }

    public function findByUrl(Url $url): array
    {
        return $this->findBy(['url' => $url], ['clickedAt' => 'DESC']);
    }

    public function countUniqueByUrl(Url $url): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(DISTINCT c.ip)')
            ->where('c.url = :urlId')
            ->andWhere('c.ip IS NOT NULL')
            ->setParameter('urlId', $url->getId(), UlidType::NAME)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByUrlGroupedByDay(Url $url): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $rows = $conn->fetchAllAssociative(
            'SELECT DATE(created_at) as day, COUNT(*) as count
             FROM clicks
             WHERE url_id = :urlId
             GROUP BY day
             ORDER BY day DESC',
            ['urlId' => $url->getId()->toBinary()],
        );

        return array_map(
            static fn(array $row) => ['day' => $row['day'], 'count' => (int) $row['count']],
            $rows,
        );
    }

    public function countByUrlGroupedByReferer(Url $url): array
    {
        return $this->countByUrlGroupedByColumn($url, 'referer', 'Direct');
    }

    public function countByUrlGroupedByBrowser(Url $url): array
    {
        return $this->countByUrlGroupedByColumn($url, 'browser', 'Unknown');
    }

    public function countByUrlGroupedByDevice(Url $url): array
    {
        return $this->countByUrlGroupedByColumn($url, 'device', 'Unknown');
    }

    private function countByUrlGroupedByColumn(Url $url, string $column, string $nullLabel): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT COALESCE($column, '') as value, COUNT(*) as count
             FROM clicks
             WHERE url_id = :urlId
             GROUP BY $column
             ORDER BY count DESC",
            ['urlId' => $url->getId()->toBinary()],
        );

        return array_map(
            static fn(array $row) => [$column => $row['value'] ?: $nullLabel, 'count' => (int) $row['count']],
            $rows,
        );
    }

    public function save(Click $click, bool $flush = false): void
    {
        $this->getEntityManager()->persist($click);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
