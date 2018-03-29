<?php

namespace AppBundle\Entity;

use AppBundle\DTO\UserDto;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * Class AppUser
 * @package AppBundle\Entity
 *
 * @ORM\Entity()
 * @ORM\Table(name="users")
 */
class AppUser //extends BaseUser
{
    /**
     * @var Uuid $id
     * @ORM\Id()
     * @ORM\Column(name="id", type="uuid", unique=true, nullable=false)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
     */
    protected $id;

    /**
     * @var string $first
     * @ORM\Column(name="first", type="string", nullable=true)
     */
    private $first;

    /**
     * @var string $last
     * @ORM\Column(name="last", type="string", nullable=true)
     */
    private $last;

    /**
     * @var ArrayCollection $appRoles
     * @ORM\ManyToMany(targetEntity="AppRole")
     * @ORM\JoinTable(
     *     name="users_roles",
     *     joinColumns={@ORM\JoinColumn(name="userId", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="roleId", referencedColumnName="id")}
     * )
     */
    private $appRoles;

    public function __construct($first, $last)
    {
        $this->id = Uuid::uuid4();
        $this->first = $first;
        $this->last = $last;
        $this->appRoles = new ArrayCollection();
    }

    public function isNamed(string $fullNameToTest)
    {
        return $fullNameToTest === $this->first.' '.$this->last;
    }

    public function getId()
    {
        return $this->id;
    }

    public function assignRole(AppRole $role)
    {
        $this->appRoles->add($role);
    }

    public function hasAppRole(AppRole $role)
    {
        $hasRoleCriteria =
            Criteria::create()->andWhere(
                Criteria::expr()->eq(
                    'id',
                    $role->getId()
                )
            );
        return $this->appRoles->matching($hasRoleCriteria)->count() > 0;
    }
}
