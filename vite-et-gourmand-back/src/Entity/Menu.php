<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $conditionsMenu = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prixParPersonne = null;

    #[ORM\Column]
    private ?int $nbPersonnesMin = null;

    #[ORM\Column]
    private ?int $quantiteStock = null;

    #[ORM\Column]
    private ?bool $isActif = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $theme = 'Classique';

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $regime = 'Carnivore';

    #[ORM\ManyToMany(targetEntity: Plat::class, inversedBy: 'menus')]
    private Collection $plats;

    #[ORM\OneToMany(mappedBy: 'menu', targetEntity: Commande::class)]
    private Collection $commandes;

    public function __construct()
    {
        $this->plats = new ArrayCollection();$this->commandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre =$titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description =$description;

        return $this;
    }

    public function getConditionsMenu(): ?string
    {
        return $this->conditionsMenu;
    }

    public function setConditionsMenu(?string $conditionsMenu): static
    {
        $this->conditionsMenu =$conditionsMenu;

        return $this;
    }

    public function getPrixParPersonne(): ?string
    {
        return $this->prixParPersonne;
    }

    public function setPrixParPersonne(string $prixParPersonne): static
    {
        $this->prixParPersonne =$prixParPersonne;

        return $this;
    }

    public function getNbPersonnesMin(): ?int
    {
        return $this->nbPersonnesMin;
    }

    public function setNbPersonnesMin(int $nbPersonnesMin): static
    {
        $this->nbPersonnesMin =$nbPersonnesMin;

        return $this;
    }

    public function getQuantiteStock(): ?int
    {
        return $this->quantiteStock;
    }

    public function setQuantiteStock(int $quantiteStock): static
    {
        $this->quantiteStock =$quantiteStock;

        return $this;
    }

    public function isIsActif(): ?bool
    {
        return $this->isActif;
    }

    public function setIsActif(bool $isActif): static
    {
        $this->isActif =$isActif;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image =$image;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): static
    {
        $this->theme =$theme;

        return $this;
    }

    public function getRegime(): ?string
    {
        return $this->regime;
    }

    public function setRegime(?string $regime): static
    {
        $this->regime =$regime;

        return $this;
    }

    /**
     * @return Collection<int, Plat>
     */
    public function getPlats(): Collection
    {
        return $this->plats;
    }

    public function addPlat(Plat $plat): static
    {
        if (!$this->plats->contains($plat)) {
            $this->plats->add($plat);
        }

        return $this;
    }

    public function removePlat(Plat $plat): static
    {
        $this->plats->removeElement($plat);

        return $this;
    }

    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): static
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setMenu($this);
        }

        return $this;
    }

    public function removeCommande(Commande $commande): static
    {
        if ($this->commandes->removeElement($commande) &&$commande->getMenu() === $this) {$commande->setMenu(null);
        }

        return $this;
    }
}
