<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="links")
 */
class Links
{
    /**
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private string $pozycja;

    /**
     * @ORM\Column(type="string")
     */
    private $etykieta;

    /**
     * @ORM\Column(type="string")
     */
    private $link;

    /**
     * @ORM\Column(type="string")
     */
    private $strona;

    /**
     * @ORM\Column(type="string")
     */
    private $lang;





    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPozycja(): string
    {
        return $this->pozycja;
    }

    public function getEtykieta(): string
    {
        return $this->etykieta;
    }

    public function getLink(): string
    {
        return $this->link;
    }

    public function getStrona(): string
    {
        return $this->strona;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setPozycja(string $value)
    {
        $this->pozycja = $value;
    }

    public function setEtykieta(string $value)
    {
        $this->etykieta = $value;
    }

    public function setLink(string $value)
    {
        $this->link = $value;
    }

    public function setStrona(string $value)
    {
        $this->strona = $value;
    }

    public function setLang(string $value)
    {
        $this->lang = $value;
    }

    public function setId(int $value)
    {
        $this->id = $value;
    }
}
