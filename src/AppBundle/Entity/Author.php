<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AuthorRepository")
 * @ORM\Table(name="author")
 */
class Author
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
     * @ORM\Column(type="string", name="name", nullable=false)
     * @Assert\NotBlank()
     * @var string
     */
    private $name;

    /**
     * Books
     *
     * @ORM\OneToMany(targetEntity="Book", mappedBy="author")
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    private $books;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->books = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of Title
     * @param string name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the value of Books
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getBooks()
    {
        return $this->books;
    }

    /**
     * Set the value of Books
     * @param \Doctrine\Common\Collections\ArrayCollection books
     * @return self
     */
    public function setBooks(\Doctrine\Common\Collections\ArrayCollection $books)
    {
        $this->books = $books;
        foreach($books as $book) {
            $book->setAuthor($this);
        }
        return $this;
    }

    /**
     * Add a book
     * @param  Book $book Book
     * @return self
     */
    public function addBook(Book $book)
    {
        if (!$this->books->contains($book)) {
            $book->setAuthor($this);
            $this->books[] = $book;
        }
        return $this;
    }

    /**
     * Remove a book
     * @param  Book    $book Book
     * @return self
     */
    public function removeBook(Book $book)
    {
        if ($this->books->contains($book)) {
            $book->setAuthor(null);
            $this->books->removeElement($book);
        }
        return $this;
    }
}
