<?php
namespace Tests\api;

use ApiTester;
use Tests\AppBundle\LocationServiceTest;

class AcceptRideByDriverCest
{
    public function seeAcceptedRideByDriver(ApiTester $I)
    {
        $requestedRide = $I->getNewRide();
        $driver = $I->getNewDriver();

        $driverId = $driver['id'];
        $rideId = $requestedRide['id'];
        $I->acceptRideByDriver(
            $rideId,
            $driverId,
            $requestedRide['passenger']['id']
        );
        $I->assignWorkDestinationToRide($rideId);
    }
}