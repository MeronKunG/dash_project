<?php

namespace App\Repository;

use App\Entity\HdComRefTransfer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method HdComRefTransfer|null find($id, $lockMode = null, $lockVersion = null)
 * @method HdComRefTransfer|null findOneBy(array $criteria, array $orderBy = null)
 * @method HdComRefTransfer[]    findAll()
 * @method HdComRefTransfer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HdComRefTransferRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, HdComRefTransfer::class);
    }

    public function getCommissionDataByAdminId($adminId)
    {
        return $this->createQueryBuilder('h')
            ->select('h.adminId, h.tfd, h.transferAmount, h.withholdingTax, h.finalTransferAmount, h.ref2, h.initialRef')
            ->where('h.adminId = :adminId')
            ->setParameter('adminId', $adminId)
            ->orderBy('h.tfd', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
