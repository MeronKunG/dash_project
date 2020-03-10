<?php

namespace App\Repository;

use App\Entity\GlobalOrderstatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method GlobalOrderstatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method GlobalOrderstatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method GlobalOrderstatus[]    findAll()
 * @method GlobalOrderstatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GlobalOrderstatusRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, GlobalOrderstatus::class);
    }

    public function getStatusNameById($id)
    {
        return $this->createQueryBuilder('g')
            ->select('g.statuscode, g.statusnameTh')
//            ->where('g.statuscode = :id')
//            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
    }
}
