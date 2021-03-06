<?php

namespace AppBundle\Repository;

use AppBundle\Entity\AppUser;
use AppBundle\Entity\Ride;
use AppBundle\Entity\RideEvent;
use AppBundle\Entity\RideEventType;
use AppBundle\Exception\RideNotFoundException;

interface RideEventRepositoryInterface
{
    /**
     * @param Ride $ride
     * @return RideEvent
     * @throws RideNotFoundException
     */
    public function getLastEventForRide(Ride $ride): RideEvent;

    public function markRideStatusByActor(Ride $ride, AppUser $actor, RideEventType $status): RideEvent;

    public function markRideStatusByPassenger(Ride $ride, RideEventType $status): RideEvent;
}
