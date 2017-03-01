<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @ORM\Table(name="app_user")
 * @UniqueEntity("username")
 */
class User
{
    /**
     * Database Id
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * Username
     *
     * @ORM\Column(type="string", name="username", nullable=false)
     * @Assert\NotBlank()
     */
    private $username;

    /**
     * Foos
     *
     * @ORM\OneToMany(targetEntity="Foo", mappedBy="user")
     */
    private $foos;

    /**
     * Get the value of Database Id
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of Username
     * @return mixed
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the value of Username
     * @param mixed username
     * @return self
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Set foos
     * @param  DoctrineCommonCollectionsArrayCollection $foos Foos
     * @return self
     */
    public function setFoos(\Doctrine\Common\Collections\ArrayCollection $foos)
    {
        $this->foos = $foos;
        foreach($this->foos as $foo) {
            $foo->setUser($this);
        }
        return $this;
    }

    /**
     * Add a foo
     * @param  Foo $foo Foo
     * @return self
     */
    public function addFoo(Foo $foo)
    {
        if (!$this->foos->contains($foo)) {
            $foo->setUser($this);
            $this->foos[] = $foo;
        }
        return $this;
    }

    /**
     * Remove a foo
     * @param  Foo    $foo Foo
     * @return self
     */
    public function removeFoo(Foo $foo)
    {
        if ($this->foos->contains($foo)) {
            $this->foos->remove($foo);
        }
        return $this;
    }
}
