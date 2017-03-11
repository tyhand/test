<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BookRepository")
 * @ORM\Table(name="book")
 */
class Book
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
     * Title
     *
     * @ORM\Column(type="string", name="title", nullable=false)
     * @Assert\NotBlank()
     * @var string
     */
    private $title;

    /**
     * Genre
     *
     * @ORM\Column(type="string", name="genre", nullable=false)
     * @Assert\NotBlank()
     * @var string
     *
     */
    private $genre;

    /**
     * Author
     *
     * @ORM\ManyToOne(targetEntity="Author", inversedBy="books")
     * @var Author
     */
    private $author;

    /**
     * Get the value of Database Id
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of Title
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the value of Title
     * @param string title
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get the value of Genre
     * @return string
     */
    public function getGenre()
    {
        return $this->genre;
    }

    /**
     * Set the value of Genre
     * @param string genre
     * @return self
     */
    public function setGenre($genre)
    {
        $this->genre = $genre;
        return $this;
    }

    /**
     * Get the value of Author
     * @return Author
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Set the value of Author
     * @param Author author
     * @return self
     */
    public function setAuthor(Author $author)
    {
        $this->author = $author;
        return $this;
    }
}
