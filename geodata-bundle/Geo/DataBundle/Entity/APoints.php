<?php
 namespace Geo\DataBundle\Entity;
 use Doctrine\ORM\Mapping as ORM;
 use Geodata\Model as ORMBehaviors;
 use Geodata\ORM\Type\LocPoint;
 /**
 * @ORM\Entity
 * @ORM\Table(name="apoints")
 */
 class APoints {
 /**
 * @ORM\Id
 * @ORM\Column(type="integer")
 * @ORM\GeneratedValue(strategy="AUTO")
 */
 protected $id;
 /**
 * @ORM\Column(type="text")
 */
 protected $title;
 /**
 * @ORM\Column(type="locpoint")
 */
 protected $point;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return APoints
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set point
     *
     * @param apoint $point
     * @return APoints
     */
    public function setPoint($point)
    {
        $this->point = $point;
    
        return $this;
    }

    /**
     * Get point
     *
     * @return apoint 
     */
    public function getPoint()
    {
        return $this->point;
    }
}