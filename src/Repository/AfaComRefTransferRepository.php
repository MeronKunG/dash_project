<?php

namespace App\Repository;

use App\Entity\AfaComRefTransfer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AfaComRefTransfer|null find($id, $lockMode = null, $lockVersion = null)
 * @method AfaComRefTransfer|null findOneBy(array $criteria, array $orderBy = null)
 * @method AfaComRefTransfer[]    findAll()
 * @method AfaComRefTransfer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AfaComRefTransferRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AfaComRefTransfer::class);
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
