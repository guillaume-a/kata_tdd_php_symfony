<?php


namespace AppBundle\Controller;

use AppBundle\Entity\AppUser;
use AppBundle\Exception\UnauthorizedOperationException;
use AppBundle\Exception\UserNotFoundException;
use AppBundle\Repository\LocationRepository;
use AppBundle\Repository\RideEventRepository;
use AppBundle\Repository\RideRepository;
use AppBundle\Repository\UserRepository;
use AppBundle\Service\LocationService;
use AppBundle\Service\RideService;
use AppBundle\Service\RideTransitionService;
use AppBundle\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\FOSRestController;
use Ramsey\Uuid\Uuid;

class AppController extends FOSRestController
{
    private $rideTransitionService;
    /** @var RideService $rideService */
    private $rideService;

    /** @var UserService $userService */
    private $userService;

    /**
     * @return UserService
     */
    protected function user() : UserService
    {
        $authenticatedUser = $this->getUser();
        if (is_null($this->userService)) {
            $this->userService = new UserService(new UserRepository(
                $this->em(),
                $this->container->get('fos_user.user_manager.public')
            ));
            if (! is_null($authenticatedUser)) {
                $this->userService->setAuthenticatedUser(
                    $authenticatedUser
                );
            }
        }
        return $this->userService;
    }

    /**
     * @return RideService
     */
    protected function ride() : RideService
    {
        if (is_null($this->rideService)) {
            $this->rideService = new RideService(
                new RideRepository($this->em()),
                new RideEventRepository($this->em())
            );
        }

        return $this->rideService;
    }

    protected function rideTransition() : RideTransitionService
    {
        if (is_null($this->rideTransitionService)) {
            $this->rideTransitionService = new RideTransitionService(
                $this->ride(),
                $this->user()
            );
        }

        return $this->rideTransitionService;
    }

    /**
     * @return LocationService
     */
    protected function location() : LocationService
    {
        return new LocationService(
            new LocationRepository($this->em())
        );
    }

    /**
     * @param string $id
     * @return Uuid
     */
    protected function id(string $id)
    {
        /** @var Uuid $uuid */
        $uuid = Uuid::fromString($id);
        return $uuid;
    }

    /**
     * @return EntityManagerInterface
     */
    private function em()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getDoctrine()->getManager();
        return $em;
    }

    /**
     * @param string $id
     * @return AppUser
     * @throws UserNotFoundException
     * @throws UnauthorizedOperationException
     */
    protected function getUserById(string $id): AppUser
    {
        return $this->user()->getUserById($this->id($id));
    }
}
