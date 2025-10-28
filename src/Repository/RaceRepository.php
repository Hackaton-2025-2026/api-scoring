<?php

namespace App\Repository;

use App\Entity\Race;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Race>
 *
 * @method Race|null find($id, $lockMode = null, $lockVersion = null)
 * @method Race|null findOneBy(array $criteria, array $orderBy = null)
 * @method Race[]    findAll()
 * @method Race[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Race::class);
    }

    /**
     * @return Race[]
     */
    public function findByStatus(string $status = null, string $sort = null): array
    {
        $qb = $this->createQueryBuilder('r');

        if ($status) {
            $today = new \DateTime();
            if ($status === 'past') {
                $qb->andWhere('r.startDate < :today');
                $qb->setParameter('today', $today);
            } elseif ($status === 'current') {
                $startOfDay = (clone $today)->setTime(0, 0, 0);
                $endOfDay = (clone $today)->setTime(23, 59, 59);
                $qb->andWhere('r.startDate BETWEEN :start AND :end')
                    ->setParameter('start', $startOfDay)
                    ->setParameter('end', $endOfDay);
            } elseif ($status === 'future') {
                $qb->andWhere('r.startDate > :today');
                $qb->setParameter('today', $today);
            }
        }

        if ($sort) {
            if ($sort === 'date_asc') {
                $qb->orderBy('r.startDate', 'ASC');
            } elseif ($sort === 'date_desc') {
                $qb->orderBy('r.startDate', 'DESC');
            }
        }

        return $qb->getQuery()->getResult();
    }
}
