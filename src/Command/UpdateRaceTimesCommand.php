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

#[AsCommand(
    name: 'app:update-race-times',
    description: 'Updates race times and ranks for unfinished runners in live.',
)]
class UpdateRaceTimesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RaceRepository $raceRepository,
        private ResultRepository $resultRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $races = $this->raceRepository->findAll();

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

            // Update times for unfinished runners
            foreach ($unfinishedResults as $result) {
                $currentTime = \DateTime::createFromFormat('H:i:s', $result->getTime());
                if (!$currentTime) {
                    $currentTime = new \DateTime('00:00:00'); // Start from 0 if time is null
                }

                $secondsToAdd = mt_rand(5, 15); // Add 5 to 15 seconds
                $currentTime->modify("+$secondsToAdd seconds");
                $result->setTime($currentTime->format('H:i:s'));
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
        }

        $this->entityManager->flush();

        $io->success('Race times and ranks updated successfully.');

        return Command::SUCCESS;
    }
}
