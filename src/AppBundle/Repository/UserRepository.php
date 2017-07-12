<?php


namespace AppBundle\Repository;

use AppBundle\Entity\AppRole;
use AppBundle\Entity\AppUser;
use AppBundle\Entity\UserRole;

class UserRepository extends AppRepository
{
    public function newUser($firstName, $lastName)
    {
        $user = new AppUser($firstName, $lastName);
        $this->save($user);
    }

    /**
     * @param integer $userId
     * @return AppUser
     */
    public function getUserById($userId)
    {
        return $this->em->createQuery(
            'select u from E:AppUser u where u.id = :userId'
        )
            ->setParameter('userId', $userId)
            ->getSingleResult();
    }

    public function assignRoleToUser(AppUser $user, AppRole $role)
    {
        $storedRole = $this->getStoredRole($role);
        $this->save(new UserRole($user, $storedRole));
    }

    private function getStoredRole(AppRole $role)
    {
        return $this->em->createQuery(
            'select r from E:AppRole r where r = :role'
        )
            ->setParameter('role', $role)
            ->getSingleResult();
    }

    public function isUserInRole(AppUser $user, AppRole $role)
    {
        $matchingRoleCount = $this->em->createQuery(
            'select count(distinct ur.id) from E:UserRole ur where ur.user = :user and ur.role = :role'
        )
            ->setParameter('user', $user)
            ->setParameter('role', $role)
            ->getSingleScalarResult();

        return ((int)$matchingRoleCount) === 1;
    }
}
