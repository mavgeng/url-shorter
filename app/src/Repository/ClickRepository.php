<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Click;
use App\Entity\Url;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.url = :url')
            ->setParameter('url', $url)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Click[]
     */
    public function findByUrl(Url $url): array
    {
        return $this->findBy(['url' => $url], ['clickedAt' => 'DESC']);
    }

    public function save(Click $click, bool $flush = false): void
    {
        $this->getEntityManager()->persist($click);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}