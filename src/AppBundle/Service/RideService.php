<?php

namespace AppBundle\Service;

use AppBundle\Entity\AppLocation;
use AppBundle\Entity\AppRole;
use AppBundle\Entity\AppUser;
use AppBundle\Entity\Ride;
use AppBundle\Entity\RideEventType;
use AppBundle\Exception\ActingDriverIsNotAssignedDriverException;
use AppBundle\Exception\RideLifeCycleException;
use AppBundle\Exception\RideNotFoundException;
use AppBundle\Exception\UserNotInDriverRoleException;
use AppBundle\Exception\UserNotInPassengerRoleException;
use AppBundle\Repository\RideEventRepositoryInterface;
use AppBundle\Repository\RideRepositoryInterface;
use Ramsey\Uuid\Uuid;

class RideService
{
    /**
     * @var RideRepositoryInterface
     */
    private $rideRepository;
    /**
     * @var RideEventRepositoryInterface
     */
    private $rideEventRepository;

    /**
     * RideService constructor.
     * @param RideRepositoryInterface $rideRepository
     * @param RideEventRepositoryInterface $rideEventRepository
     */
    public function __construct(
        RideRepositoryInterface $rideRepository,
        RideEventRepositoryInterface $rideEventRepository
    ) {
        $this->rideRepository = $rideRepository;
        $this->rideEventRepository = $rideEventRepository;
    }

    /**
     * @param AppUser $passenger
     * @param AppLocation $departure
     * @return Ride
     * @throws UserNotInPassengerRoleException
     */
    public function newRide(AppUser $passenger, AppLocation $departure)
    {
        $this->validateUserHasPassengerRole($passenger);

        $newRide = new Ride($passenger, $departure);
        $this->rideRepository->saveRide($newRide);

        $this->rideEventRepository->markRideStatusByPassenger(
            $newRide,
            RideEventType::requested()
        );

        return $newRide;
    }

    /**
     * @param Uuid $id
     * @return mixed
     * @throws RideNotFoundException
     */
    public function getRide(Uuid $id)
    {
        return $this->rideRepository->getRideById($id);
    }

    /**
     * @param Ride $ride
     * @return RideEventType
     * @throws RideNotFoundException
     */
    public function getRideStatus(Ride $ride)
    {
        return
            $this->rideEventRepository
                ->getLastEventForRide($ride)
                ->getStatus();
    }

    /**
     * @param Ride $ride
     * @param AppUser $driver
     * @return Ride
     * @throws RideLifeCycleException
     * @throws RideNotFoundException
     * @throws UserNotInDriverRoleException
     */
    public function acceptRide(Ride $ride, AppUser $driver)
    {
        $this->validateUserHasDriverRole($driver);
        $this->validateRideIsRequested($ride);

        $this->markRide($ride, $driver, RideEventType::accepted());

        $this->rideRepository->assignDriverToRide(
            $ride,
            $driver
        );

        return $ride;
    }

    public function assignDestinationToRide(Ride $ride, AppLocation $destination)
    {
        $this->rideRepository->assignDestinationToRide(
            $ride,
            $destination
        );
        return $ride;
    }

    /**
     * @param Ride $acceptedRide
     * @param AppUser $driver
     * @return Ride
     * @throws ActingDriverIsNotAssignedDriverException
     * @throws RideLifeCycleException
     * @throws UserNotInDriverRoleException
     * @throws RideNotFoundException
     */
    public function markRideInProgress(Ride $acceptedRide, AppUser $driver)
    {
        $this->validateRideIsAccepted($acceptedRide);
        $this->validateUserHasDriverRole($driver);
        $this->validateAttemptingDriverIsAssignedDriver($acceptedRide, $driver);

        $this->markRide(
            $acceptedRide,
            $driver,
            RideEventType::inProgress()
        );
        return $acceptedRide;
    }

    /**
     * @param Ride $rideInProgress
     * @param AppUser $driver
     * @return Ride
     * @throws ActingDriverIsNotAssignedDriverException
     * @throws RideLifeCycleException
     * @throws RideNotFoundException
     */
    public function markRideCompleted(Ride $rideInProgress, AppUser $driver)
    {
        $this->validateAttemptingDriverIsAssignedDriver($rideInProgress, $driver);
        $this->validateRideIsInProgress($rideInProgress);

        $this->markRide(
            $rideInProgress,
            $driver,
            RideEventType::completed()
        );
        return $rideInProgress;
    }

    /**
     * @param AppUser $passenger
     * @throws UserNotInPassengerRoleException
     */
    private function validateUserHasPassengerRole(AppUser $passenger)
    {
        if (!$passenger->hasRole(AppRole::passenger())) {
            throw new UserNotInPassengerRoleException();
        }
    }

    /**
     * @param AppUser $driver
     * @throws UserNotInDriverRoleException
     */
    private function validateUserHasDriverRole(AppUser $driver)
    {
        if (!$driver->hasRole(AppRole::driver())) {
            throw new UserNotInDriverRoleException();
        }
    }

    /**
     * @param Ride $ride
     * @throws RideLifeCycleException
     * @throws RideNotFoundException
     */
    private function validateRideIsRequested(Ride $ride)
    {
        if (!RideEventType::requested()->equals(
            $this->getRideStatus($ride)
        )) {
            throw new RideLifeCycleException();
        }
    }

    /**
     * @param Ride $acceptedRide
     * @throws RideLifeCycleException
     * @throws RideNotFoundException
     */
    private function validateRideIsAccepted(Ride $acceptedRide)
    {
        if (!RideEventType::accepted()->equals(
            $this->getRideStatus($acceptedRide)
        )) {
            throw new RideLifeCycleException();
        }
    }

    /**
     * @param Ride $acceptedRide
     * @param AppUser $driver
     * @throws ActingDriverIsNotAssignedDriverException
     */
    private function validateAttemptingDriverIsAssignedDriver(Ride $acceptedRide, AppUser $driver)
    {
        if (!$acceptedRide->isDrivenBy($driver)) {
            throw new ActingDriverIsNotAssignedDriverException();
        }
    }

    /**
     * @param Ride $rideInProgress
     * @throws RideLifeCycleException
     * @throws RideNotFoundException
     */
    private function validateRideIsInProgress(Ride $rideInProgress)
    {
        if (!RideEventType::inProgress()->equals($this->getRideStatus($rideInProgress))) {
            throw new RideLifeCycleException();
        }
    }

    private function markRide(Ride $ride, AppUser $driver, RideEventType $status)
    {
        $this->rideEventRepository->markRideStatusByActor(
            $ride,
            $driver,
            $status
        );
    }
}
