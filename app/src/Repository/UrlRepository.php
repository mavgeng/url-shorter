<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Url;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Url>
 */
class UrlRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Url::class);
    }

    public function findByShortCode(string $shortCode): ?Url
    {
        return $this->findOneBy(['shortCode' => $shortCode]);
    }

    public function save(Url $url, bool $flush = false): void
    {
        $this->getEntityManager()->persist($url);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Url $url, bool $flush = false): void
    {
        $this->getEntityManager()->remove($url);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
