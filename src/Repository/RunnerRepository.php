<?php

namespace App\Repository;

use App\Entity\Runner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Runner>
 *
 * @method Runner|null find($id, $lockMode = null, $lockVersion = null)
 * @method Runner|null findOneBy(array $criteria, array $orderBy = null)
 * @method Runner[]    findAll()
 * @method Runner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RunnerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Runner::class);
    }
}
