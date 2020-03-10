<?php

namespace App\Repository;

use App\Entity\TestSalesComDaily;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method TestSalesComDaily|null find($id, $lockMode = null, $lockVersion = null)
 * @method TestSalesComDaily|null findOneBy(array $criteria, array $orderBy = null)
 * @method TestSalesComDaily[]    findAll()
 * @method TestSalesComDaily[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TestSalesComDailyRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TestSalesComDaily::class);
    }

    public function getAllData($adminId)
    {
        return $this->createQueryBuilder('t1')
            ->select('t1.comAmt AS cd_amt')
            ->where('t1.adminid = :adminId')
            ->setParameter('adminId', $adminId)
            ->getQuery()
            ->getResult();
    }
}
