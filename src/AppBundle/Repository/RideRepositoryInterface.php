<?php

namespace AppBundle\Repository;

use AppBundle\Entity\AppLocation;
use AppBundle\Entity\AppUser;
use AppBundle\Entity\Ride;
use AppBundle\Exception\RideNotFoundException;
use Ramsey\Uuid\Uuid;

interface RideRepositoryInterface
{
    public function assignDestinationToRide(Ride $ride, AppLocation $destination);

    /**
     * @param Uuid $id
     * @return Ride
     * @throws RideNotFoundException
     */
    public function getRideById(Uuid $id) : Ride;

    public function assignDriverToRide(Ride $ride, AppUser $driver): void;

    public function saveRide(Ride $newRide): void;
}
