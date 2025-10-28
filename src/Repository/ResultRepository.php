<?php

namespace App\Repository;

use App\Entity\Result;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Result>
 *
 * @method Result|null find($id, $lockMode = null, $lockVersion = null)
 * @method Result|null findOneBy(array $criteria, array $orderBy = null)
 * @method Result[]    findAll()
 * @method Result[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Result::class);
    }

    /**
     * @return Result[]
     */
    public function findByRace(int $raceId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.race = :raceId')
            ->setParameter('raceId', $raceId)
            ->orderBy('r.runnerRank', 'ASC') // Add this line for sorting
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Result[]
     */
    public function findByRunner(int $runnerId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.runner = :runnerId')
            ->setParameter('runnerId', $runnerId)
            ->getQuery()
            ->getResult();
    }
}
