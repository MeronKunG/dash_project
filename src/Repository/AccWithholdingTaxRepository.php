<?php

namespace App\Repository;

use App\Entity\TestAccWithholdingTax;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method TestAccWithholdingTax|null find($id, $lockMode = null, $lockVersion = null)
 * @method TestAccWithholdingTax|null findOneBy(array $criteria, array $orderBy = null)
 * @method TestAccWithholdingTax[]    findAll()
 * @method TestAccWithholdingTax[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AccWithholdingTaxRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TestAccWithholdingTax::class);
    }

    public function getFileNameByUserId($userId, string $date)
    {
        return $this->createQueryBuilder('t')
            ->select('t.recordId, t.globalAuthenId, t.transactionDate, t.fileUrl, t.timestamp, t.uploadedBy')
            ->where('t.globalAuthenId = :globalAuthenId')
            ->andWhere('DATE_FORMAT(t.transactionDate, \'%Y-%m\') = :date')
            ->setParameter('globalAuthenId', $userId)
            ->setParameter('date', $date)
            ->orderBy('t.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getUploadNameByUserId($userId)
    {
        return $this->createQueryBuilder('g')
            ->select('t.displayname')
            ->leftJoin('App\Entity\TestCsRemarksUser', 't', 'WITH', 'g.uploadedBy = t.id')
            ->where('g.uploadedBy = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findAllDataQuery($userId, string $query)
    {
        return $this->createQueryBuilder('g')
            ->select('g.recordId, g.globalAuthenId, g.transactionDate, g.fileUrl, g.timestamp, g.uploadedBy')
            ->join('App\Entity\TestCsRemarksUser', 't', 'WITH', 'g.uploadedBy = t.id')
            ->where('g.globalAuthenId = :globalAuthenId AND g.fileUrl LIKE :query')
            ->setParameter('globalAuthenId', $userId)
            ->setParameter('query', $query . '%')
            ->orderBy('g.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function findDateQuery($userId, string $startDate, string $endDate)
    {
        return $this->createQueryBuilder('t')
            ->select('t.recordId, t.globalAuthenId, t.transactionDate, t.fileUrl, t.timestamp, t.uploadedBy')
            ->join('App\Entity\GlobalAuthen', 'g', 'WITH', 't.globalAuthenId = g.id')
            ->where('t.globalAuthenId = :globalAuthenId')
            ->andWhere('t.transactionDate BETWEEN :startDate AND :endDate')
            ->setParameter('globalAuthenId', $userId)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('t.transactionDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
