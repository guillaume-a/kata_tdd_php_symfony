<?php
namespace Tests\api;

use ApiTester;

class MarkRideCompletedCest
{
    /**
     * @depends Tests\api\MarkRideInProgressCest:seeRideMarkedInProgress
     * @param ApiTester $I
     */
    public function seeRideMarkedCompleted(ApiTester $I)
    {
        $requestedRide = $I->getNewRide();
        $driver = $I->getNewDriver();

        $driverId = $driver['id'];
        $rideId = $requestedRide['id'];
        $passengerId = $requestedRide['passenger']['id'];

        $I->acceptRideByDriver(
            $rideId,
            $driverId,
            $passengerId
        );
        $I->assignWorkDestinationToRide($rideId);
        $I->markRideInProgress($rideId, $driverId);
        $I->markRideCompleted($rideId, $driverId);
    }
}
