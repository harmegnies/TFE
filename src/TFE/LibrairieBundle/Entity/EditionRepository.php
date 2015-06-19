<?php

namespace TFE\LibrairieBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * EditionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EditionRepository extends EntityRepository
{
    public function getListe()
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.nomEdition', 'ASC')
            ->getQuery()
            ->getResult();
    }
}