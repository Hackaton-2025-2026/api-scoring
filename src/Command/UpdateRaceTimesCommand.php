<?php

namespace App\Command;

use App\Repository\RaceRepository;
use App\Repository\ResultRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(
    name: 'app:update-race-times',
    description: 'Updates race times and ranks for unfinished runners in live.',
)]
class UpdateRaceTimesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RaceRepository $raceRepository,
        private ResultRepository $resultRepository,
        private PublisherInterface $publisher,
        private SerializerInterface $serializer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $races = $this->raceRepository->findAll();
        $updatedRaces = [];

        foreach ($races as $race) {
            // Check if the race has started
            if ($race->getStartDate() > new \DateTime()) {
                continue; // Skip races that haven't started yet
            }

            $raceResults = $this->resultRepository->findBy(['race' => $race]);

            // Filter out finished runners and prepare for time update
            $unfinishedResults = [];
            foreach ($raceResults as $result) {
                if (!$result->isHasFinished()) {
                    $unfinishedResults[] = $result;
                }
            }

            // If there are no unfinished results, the race is considered finished, so skip it
            if (empty($unfinishedResults)) {
                continue;
            }

            // Find the leading unfinished runner (the one with the best time among unfinished)
            // Since raceResults is sorted by time, the first unfinished result in the sorted list
            // will be the leading unfinished runner.
            $leadingUnfinishedRunner = null;
            foreach ($raceResults as $result) {
                if (!$result->isHasFinished()) {
                    $leadingUnfinishedRunner = $result;
                    break;
                }
            }

            // Update Race's kilometer based on the leading unfinished runner's progress
            if ($leadingUnfinishedRunner) {
                $currentRaceKilometer = $race->getKilometer();
                $raceDistance = $race->getDistance();

                // Increase kilometer by a random amount (e.g., 0.1 to 0.5 km per minute)
                $kmToAdd = mt_rand(10, 50) / 100; // 0.1 to 0.5 km
                $newRaceKilometer = $currentRaceKilometer + $kmToAdd;

                // Ensure race kilometer does not exceed race distance
                if ($newRaceKilometer > $raceDistance) {
                    $newRaceKilometer = $raceDistance;
                }
                $race->setKilometer($newRaceKilometer);
                $this->entityManager->persist($race);

                // If race kilometer reaches race distance, mark the leading runner as finished
                if ($race->getKilometer() >= $raceDistance && !$leadingUnfinishedRunner->isHasFinished()) {
                    $leadingUnfinishedRunner->setHasFinished(true);
                    $race->setKilometer($raceDistance); // Set race kilometer to distance when leading runner finishes
                    $this->entityManager->persist($leadingUnfinishedRunner);
                }
            }

            // Update times for unfinished runners
            foreach ($unfinishedResults as $result) {
                $currentTime = \DateTime::createFromFormat('H:i:s', $result->getTime());
                if (!$currentTime) {
                    $currentTime = new \DateTime('00:00:00'); // Start from 0 if time is null
                }

                $secondsToAdd = mt_rand(5, 15); // Add 5 to 15 seconds
                $currentTime->modify("+$secondsToAdd seconds");
                $result->setTime($currentTime->format('H:i:s'));

                // Randomly mark some unfinished runners as finished (e.g., 10% chance)
                if (mt_rand(1, 10) === 1) { 
                    $result->setHasFinished(true);
                }

                $this->entityManager->persist($result);
            }

            // Re-sort all results for the current race to determine new ranks
            usort($raceResults, function ($a, $b) {
                // Non-finishers should have a "worse" rank, so they come after finishers
                if ($a->isHasFinished() && !$b->isHasFinished()) {
                    return -1; // a (finished) comes before b (not finished)
                }
                if (!$a->isHasFinished() && $b->isHasFinished()) {
                    return 1; // b (finished) comes before a (not finished)
                }
                // If both finished or both not finished, compare by time
                return strcmp($a->getTime(), $b->getTime());
            });

            // Assign new ranks based on sorted order
            foreach ($raceResults as $index => $result) {
                $result->setRunnerRank($index + 1);
                $this->entityManager->persist($result);
            }
            $updatedRaces[] = $race;
        }

        $this->entityManager->flush();

        // Publish Mercure updates for all updated races
        foreach ($updatedRaces as $race) {
            // Serialize the race object to JSON
            // You might need to configure serialization groups in your Race entity
            // to control what data is exposed via Mercure.
            $json = $this->serializer->serialize($race, 'json', ['groups' => ['race:read']]);
            $update = new Update(
                getenv('API_PUBLIC_URL') . '/races/' . $race->getId(), // Topic URL
                $json
            );
            $this->publisher->__invoke($update);
        }

        $io->success('Race times and ranks updated successfully.');

        return Command::SUCCESS;
    }
}
