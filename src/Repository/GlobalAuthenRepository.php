<?php

namespace App\Repository;

use App\Entity\GlobalAuthen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method GlobalAuthen|null find($id, $lockMode = null, $lockVersion = null)
 * @method GlobalAuthen|null findOneBy(array $criteria, array $orderBy = null)
 * @method GlobalAuthen[]    findAll()
 * @method GlobalAuthen[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GlobalAuthenRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, GlobalAuthen::class);
    }

    public function getUsername($username, $merchantName)
    {
        return $this->createQueryBuilder('g')
            ->select('g, mc')
            ->leftJoin('App\Entity\MerchantConfig', 'mc', 'WITH', 'g.merid = mc.takeorderby')
            ->where('g.username = :username')
            ->andWhere('mc.merchantname = :merchantname')
            ->setParameter('username', $username)
            ->setParameter('merchantname', $merchantName)
            ->getQuery()
            ->getResult();
    }

    public function getUsernameByPhone($phoneNo)
    {
        return $this->createQueryBuilder('g')
            ->select('g, mc')
            ->leftJoin('App\Entity\MerchantConfig', 'mc', 'WITH', 'g.merid = mc.takeorderby')
            ->where('g.phoneno = :phoneNo')
            ->setParameter('phoneNo', $phoneNo)
            ->getQuery()
            ->getResult();
    }

    public function getUserData($adminId)
    {
        return $this->createQueryBuilder('g')
            ->select('g.id, g.fname, g.phoneno')
            ->where('g.id = :id')
            ->setParameter('id', $adminId)
            ->getQuery()
            ->getResult();
    }
}
