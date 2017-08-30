<?php


namespace Tests\AppBundle\Service;

use Tests\AppBundle\AppTestCase;
use AppBundle\Entity\AppRole;
use AppBundle\Entity\Ride;
use AppBundle\Entity\RideEventType;
use AppBundle\RideEventLifeCycleException;
use AppBundle\UnassignedDriverException;

class RideServiceTest extends AppTestCase
{
    public function testCreateRideForPassengerAndDeparture()
    {
        $firstRide = $this->makePassengerRide();
        self::assertEquals($this->userOne->getFullName(), $firstRide->getPassenger()->getFullName());
        self::assertTrue($this->home->equals($firstRide->getDeparture()));
    }

    public function testAssignDestinationToRide()
    {
        $ride = $this->makePassengerRide();
        $this->rideService->assignDestinationToRide($ride, $this->work);

        $ride = $this->getFirstRideForUserOne();
        self::assertTrue($ride->getDestination()->equals($this->work));
    }

    public function testMarkRideAsRequested()
    {
        $ride = $this->makePassengerRide();
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asRequested());

        $rideStatus = $this->rideService->getRideStatus($ride);
        self::assertEquals("Requested", $rideStatus->getType()->getName());
    }

    public function testCheckRideStatus()
    {
        $ride = $this->makePassengerRide();
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asRequested());

        self::assertTrue($this->rideService->isRide($ride, RideEventType::asRequested()));
    }

    public function testAssignDriverToRide()
    {
        $ride = $this->makePassengerRide();
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asRequested());

        $this->assignDriverToRide($ride);

        self::assertTrue($this->userService->isUserDriver($ride->getDriver()));
    }

    public function testRideEventLifeCycles()
    {
        $ride = $this->makePassengerRide();
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asRequested());
        self::assertTrue($this->rideService->isRide($ride, RideEventType::asRequested()));

        $this->assignDriverToRide($ride);

        $this->rideService->driverMarkRideAs($ride, RideEventType::asAccepted());
        self::assertTrue($this->rideService->isRide($ride, RideEventType::asAccepted()));

        $this->rideService->passengerMarkRideAs($ride, RideEventType::asDestination());
        self::assertTrue($this->rideService->isRide($ride, RideEventType::asDestination()));

        $this->rideService->driverMarkRideAs($ride, RideEventType::inProgress());
        self::assertTrue($this->rideService->isRide($ride, RideEventType::inProgress()));

        $this->rideService->driverMarkRideAs($ride, RideEventType::asCompleted());
        self::assertTrue($this->rideService->isRide($ride, RideEventType::asCompleted()));
    }

    public function testMarkRideAsAssignedDestinationFromRequested() {

        $ride = $this->getRequestedPassengerRide();
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asDestination());
        $this->rideService->assignDestinationToRide($ride, $this->work);
        self::assertTrue($this->rideService->isRide($ride, RideEventType::asDestination()));
    }

    public function testMarkRideAsAssignedDestinationFromAccepted()
    {
        $ride = $this->getAcceptedPassengerRide();
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asDestination());
        $this->rideService->assignDestinationToRide($ride, $this->work);
        self::assertTrue($this->rideService->isRide($ride, RideEventType::asDestination()));
    }

    public function testOutOfSequenceDestinationAssignmentThrows()
    {
        $ride = $this->getAcceptedPassengerRide();
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asDestination());
        $this->rideService->driverMarkRideAs($ride, RideEventType::inProgress());
        self::expectException(RideEventLifeCycleException::class);
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asDestination());
    }

    public function testOutOfSequenceRequestedEventThrows()
    {
        $ride = $this->makePassengerRide();
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asRequested());
        self::expectException(RideEventLifeCycleException::class);
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asRequested());
    }

    public function testUnassignedDriverThrows()
    {
        $ride = $this->makePassengerRide();
        self::expectException(UnassignedDriverException::class);
        $this->rideService->driverMarkRideAs($ride, RideEventType::asAccepted());

    }

    public function testOutOfSequenceAcceptedEventThrows()
    {
        $ride = $this->makePassengerRide();
        $this->assignDriverToRide($ride);
        self::expectException(RideEventLifeCycleException::class);
        $this->rideService->driverMarkRideAs($ride, RideEventType::asAccepted());
    }

    public function testOutOfSequenceInProgressEventThrows()
    {
        $ride = $this->makePassengerRide();
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asRequested());
        $this->assignDriverToRide($ride);
        $this->rideService->driverMarkRideAs($ride, RideEventType::asAccepted());
        self::expectException(RideEventLifeCycleException::class);
        $this->rideService->driverMarkRideAs($ride, RideEventType::inProgress());
    }

    public function testOutOfSequenceCancelledEventThrows()
    {
        $ride = $this->getAcceptedPassengerRide();
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asDestination());
        $this->rideService->driverMarkRideAs($ride, RideEventType::inProgress());
        self::expectException(RideEventLifeCycleException::class);
        $this->rideService->driverMarkRideAs($ride, RideEventType::asCancelled());
    }

    public function testCancelledEventWorks()
    {
        $ride = $this->getAcceptedPassengerRide();
        $this->rideService->driverMarkRideAs($ride, RideEventType::asCancelled());
        self::assertTrue($this->rideService->isRide($ride, RideEventType::asCancelled()));
    }

    public function testOutOfSequenceCompletedThrows()
    {
        $ride = $this->getAcceptedPassengerRide();
        self::expectException(RideEventLifeCycleException::class);
        $this->rideService->driverMarkRideAs($ride, RideEventType::asCompleted());
    }

    public function testOutOfSequenceRejectedThrows()
    {
        $ride = $this->getAcceptedPassengerRide();
        self::expectException(RideEventLifeCycleException::class);
        $this->rideService->driverMarkRideAs($ride, RideEventType::asRejected());
    }

    public function testRejectedWorks()
    {
        $ride = $this->makePassengerRide();
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asRequested());
        $this->rideService->prospectiveDriverMarkRideAs($ride, RideEventType::asRejected(), $this->prospectiveDriver);
        self::assertTrue($this->rideService->isRide($ride, RideEventType::asRejected()));
    }

    /**
     * life-cycle:
     *
     * passenger: requestRide
     * driver: accept ride
     * passenger: set destination
     * driver: start ride
     * driver: complete ride
     */

    public function testRequestRideHasProperStateAndAttributes()
    {
        $ride = $this->getRequestedPassengerRide();

        self::assertEquals('Chris', $ride->getPassenger()->getFirstName());
        self::assertTrue($this->rideService->isRide($ride, RideEventType::asRequested()));
    }

    public function testAcceptRideHasProperStateAndAttributes()
    {
        $ride = $this->getAcceptedRequestedPassengerRide();

        self::assertTrue($ride->hasDriver());
        self::assertTrue($this->rideService->isRide($ride, RideEventType::asAccepted()));
    }

    public function testSetDestinationHasProperStateAndAttributes()
    {
        $ride = $this->getDestinationAcceptedRequestedPassengerRide();

        self::assertTrue($ride->getDestination()->equals($this->work));
        self::assertTrue($this->rideService->isRide($ride, RideEventType::asDestination()));
    }

    public function testStartRideHasProperStateAndAttributes()
    {
        $ride = $this->getDestinationAcceptedRequestedPassengerRide();

        $this->rideService->startRide($ride);

        self::assertTrue($this->rideService->isRide($ride, RideEventType::inProgress()));
    }

    public function testCompleteRideHasProperStateAndAttributes()
    {
        $ride = $this->getDestinationAcceptedRequestedPassengerRide();
        $this->rideService->startRide($ride);

        $this->rideService->completeRide($ride);

        self::assertTrue($this->rideService->isRide($ride, RideEventType::asCompleted()));
    }

    /**
     * @return Ride
     */
    private function makePassengerRide()
    {
        $this->makeUserOnePassenger();
        $this->rideService->createRide(
            $this->userOne,
            $this->home
        );

        $firstRide = $this->getFirstRideForUserOne();

        return $firstRide;
    }

    private function assignDriverToRide(Ride $ride)
    {
        $this->userService->assignRoleToUser($this->userTwo, AppRole::asDriver());
        $this->rideService->assignDriverToRide($ride, $this->userTwo);
    }

    /**
     * @return Ride
     */
    private function getAcceptedPassengerRide()
    {
        $ride = $this->makePassengerRide();
        $this->rideService->passengerMarkRideAs($ride, RideEventType::asRequested());
        $this->assignDriverToRide($ride);
        $this->rideService->driverMarkRideAs($ride, RideEventType::asAccepted());

        return $ride;
    }

    private function makeUserOnePassenger()
    {
        $this->userService->assignRoleToUser($this->userOne, AppRole::asPassenger());
    }

    /**
     * @return Ride
     */
    private function getFirstRideForUserOne()
    {
        $ridesForUser = $this->rideService->getRidesForPassenger($this->userOne);
        self::assertCount(1, $ridesForUser);
        $firstRide = $ridesForUser[0];

        return $firstRide;
    }

    /**
     * @return Ride
     */
    private function getRequestedPassengerRide()
    {
        $this->makeUserOnePassenger();
        $this->rideService->requestRide($this->userOne, $this->home);
        $firstRide = $this->getFirstRideForUserOne();

        return $firstRide;
    }

    /**
     * @return Ride
     */
    private function getAcceptedRequestedPassengerRide()
    {
        $ride = $this->getRequestedPassengerRide();
        $this->userService->assignRoleToUser($this->userTwo, AppRole::asDriver());
        $this->rideService->driverAcceptRide($ride, $this->userTwo);

        $ride = $this->getFirstRideForUserOne();

        return $ride;
    }

    /**
     * @return Ride
     */
    private function getDestinationAcceptedRequestedPassengerRide()
    {
        $ride = $this->getAcceptedRequestedPassengerRide();
        $this->rideService->setDestinationForRide($ride, $this->work);

        $ride = $this->getFirstRideForUserOne();

        return $ride;
    }
}