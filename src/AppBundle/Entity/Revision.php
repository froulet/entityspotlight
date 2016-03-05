<?php
// src/AppBundle/Entity/Revision.php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="revision")
 */
class Revision
{   

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer")
     */
    protected $idRevision;

    /**
     * @ORM\Column(type="integer")
     */
    protected $idEntity;


    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $categoryTitle;


    /**
     * @ORM\Column(type="datetime", length=100)
     */
    protected $date;


    /**
     * Get idRevision
     *
     * @return integer
     */
    public function getIdRevision()
    {
        return $this->idRevision;
    }

    /**
     * Set idRevision
     *
     * @param integer $idRevision
     *
     * @return Revision
     */
    public function setIdRevision($idRevision)
    {
        $this->idRevision = $idRevision;

        return $this;
    }

    /**
     * Set idEntity
     *
     * @param integer $idEntity
     *
     * @return Revision
     */
    public function setIdEntity($idEntity)
    {
        $this->idEntity = $idEntity;

        return $this;
    }

    /**
     * Get idEntity
     *
     * @return integer
     */
    public function getIdEntity()
    {
        return $this->idEntity;
    }

    /**
     * Set categoryTitle
     *
     * @param string $categoryTitle
     *
     * @return Revision
     */
    public function setCategoryTitle($categoryTitle)
    {
        $this->categoryTitle = $categoryTitle;

        return $this;
    }

    /**
     * Get categoryTitle
     *
     * @return string
     */
    public function getCategoryTitle()
    {
        return $this->categoryTitle;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     *
     * @return Revision
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }
}
