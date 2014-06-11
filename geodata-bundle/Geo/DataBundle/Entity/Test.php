<?php
 namespace Praktika\TestBundle\Entity;
 use Doctrine\ORM\Mapping as ORM;
 /**
 * @ORM\Entity
 * @ORM\Table(name="test")
 */
 class Test {
	 /**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	 protected $id;
	 /**
	 * @ORM\Column(type="point")
	 */
	 protected $point;
}
