<?php

namespace App\Repository;

use App\Entity\AfaComRefCheck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method AfaComRefCheck|null find($id, $lockMode = null, $lockVersion = null)
 * @method AfaComRefCheck|null findOneBy(array $criteria, array $orderBy = null)
 * @method AfaComRefCheck[]    findAll()
 * @method AfaComRefCheck[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AfaComRefCheckRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, AfaComRefCheck::class);
    }
}
