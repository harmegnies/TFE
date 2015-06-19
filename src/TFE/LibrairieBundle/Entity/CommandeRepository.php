<?php

namespace TFE\LibrairieBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * CommandeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CommandeRepository extends EntityRepository
{
    public function countAll()
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countEnAttente()
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->where('c.enAttente = true')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function listEnAttente($page, $maxParPage)
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.enAttente = true')
            ->setFirstResult(($page-1) * $maxParPage)
            ->setMaxResults($maxParPage)
            ->orderBy('c.dateCommande', 'ASC');

        return new Paginator($qb);
    }

    public function commandeComplete($id)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.utilisateur', 'uti')
            ->addSelect('uti')
            ->leftJoin('c.modeLivraison', 'ml')
            ->addSelect('ml')
            ->leftJoin('c.livreCommandes', 'lc')
            ->addSelect('lc')
            ->leftJoin('lc.livre', 'liv')
            ->addSelect('liv')
            ->leftJoin('liv.accompagnements', 'acc')
            ->addSelect('acc')
            ->getQuery()
            ->getResult();
    }

}
