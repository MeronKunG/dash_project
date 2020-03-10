<?php

namespace App\Repository;

use App\Entity\HdComRefCheck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method HdComRefCheck|null find($id, $lockMode = null, $lockVersion = null)
 * @method HdComRefCheck|null findOneBy(array $criteria, array $orderBy = null)
 * @method HdComRefCheck[]    findAll()
 * @method HdComRefCheck[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HdComRefCheckRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, HdComRefCheck::class);
    }
}
