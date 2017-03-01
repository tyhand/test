<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\FooRepository")
 * @ORM\Table(name="foo")
 */
class Foo
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
     * Name
     *
     * @ORM\Column(type="string", name="name", nullable=false)
     * @Assert\NotBlank()
     */
    private $name;

    /**
     * Secret Number
     *
     * @ORM\Column(type="integer", name="secret_number", nullable=false)
     * @Assert\NotBlank()
     */
    private $secretNumber;

    /**
     * User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="foos")
     */
    private $user;

    /**
     * Get the value of Database Id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of Name
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of Name
     *
     * @param mixed name
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of Secret Number
     *
     * @return mixed
     */
    public function getSecretNumber()
    {
        return $this->secretNumber;
    }

    /**
     * Set the value of Secret Number
     *
     * @param mixed secretNumber
     *
     * @return self
     */
    public function setSecretNumber($secretNumber)
    {
        $this->secretNumber = $secretNumber;

        return $this;
    }

    /**
     * Get the value of User
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the value of User
     * @param mixed user
     * @return self
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }
}
