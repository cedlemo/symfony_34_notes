<?php

namespace BlogBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Article
 *
 * @ORM\Table(name="article")
 * @ORM\Entity(repositoryClass="BlogBundle\Repository\ArticleRepository")
 */
class Article
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="publicationDate", type="datetime")
     */
    private $publicationDate;

    /**
     * @var bool
     *
     * @ORM\Column(name="status", type="boolean")
     */
    private $status;

    /**
     * @ORM\ManyToOne(targetEntity="BlogBundle\Entity\Author", inversedBy="articles")
     */
    private $author;

    /**
     * @ORM\OneToMany(targetEntity="BlogBundle\Entity\Comment", mappedBy="article")
     */
    private $comments;

    /**
     * @ORM\ManyToMany(targetEntity="BlogBundle\Entity\Category", inversedBy="articles")
     */
    private $categories;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return Article
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
     * Set content
     *
     * @param string $content
     *
     * @return Article
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set publicationDate
     *
     * @param \DateTime $publicationDate
     *
     * @return Article
     */
    public function setPublicationDate($publicationDate)
    {
        $this->publicationDate = $publicationDate;

        return $this;
    }

    /**
     * Get publicationDate
     *
     * @return \DateTime
     */
    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    /**
     * Set status
     *
     * @param boolean $status
     *
     * @return Article
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get author
     * @return Author
     */
    public function getAuthor()
    {
	return $this->author;
    }

    /**
     * Set author
     * @param Author $author
     * @return Article
     */
    public function setAuthor($author)
    {
	$this->author = $author;
	return $this;
    }

    /**
     * Get articles
     * @return mixed Aricle
     */
    public function getArticles()
    {
	return $this->articles;
    }

    /**
     * Set articles
     * @param mixed Article
     * @return Article
     */
    public function setArticles($articles)
    {
	$this->articles = $articles;
        return $this;
    }

    /**
     * Get categories
     * @return mixed Aricle
     */
    public function getCategories()
    {
	return $this->categories;
    }

    /**
     * Set categories
     * @param mixed Article
     * @return Article
     */
    public function setCategories($categories)
    {
	$this->categories = $categories;
        return $this;
    }
}
