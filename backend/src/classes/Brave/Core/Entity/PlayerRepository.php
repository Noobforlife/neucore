<?php

namespace Brave\Core\Entity;

/**
 * PlayerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PlayerRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * Constructor that makes this class autowireable.
     */
    public function __construct(\Doctrine\ORM\EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getMetadataFactory()->getMetadataFor(Player::class));
    }

    /**
     *
     * {@inheritDoc}
     * @see \Doctrine\ORM\EntityRepository::find()
     * @return \Brave\Core\Entity\Player|null
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    /**
     *
     * {@inheritDoc}
     * @see \Doctrine\ORM\EntityRepository::findBy()
     * @return \Brave\Core\Entity\Player[]
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }
}