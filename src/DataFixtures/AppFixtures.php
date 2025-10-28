<?php

namespace App\DataFixtures;

use App\Entity\Race;
use App\Entity\Runner;
use App\Entity\Result;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $raceNames = [
            'Marathon de Paris', 'Marathon de New York', 'Marathon de Berlin',
            'Marathon de Londres', 'Marathon de Boston', 'Marathon de Chicago',
            'Marathon de Tokyo', 'Marathon de Rome', 'Marathon de Barcelone',
            'Marathon de Séville', 'Marathon de Valence', 'Marathon de Rotterdam',
            'Marathon de Hambourg', 'Marathon de Stockholm', 'Marathon d\'Amsterdam',
            'Marathon de Dublin', 'Marathon d\'Athènes', 'Marathon de Prague',
            'Marathon de Copenhague', 'Marathon de Lisbonne', 'Marathon de Budapest',
            'Marathon de Vienne', 'Marathon de Madrid', 'Marathon de Florence',
            'Marathon de Jérusalem', 'Marathon de la Grande Muraille', 'Marathon de l Everest',
            'Marathon du Mont-Blanc', 'Marathon de la Baie du Mont-Saint-Michel', 'Marathon du Médoc'
        ];

        // Create 20 Races
        $races = [];
        $numberOfCurrentRaces = 5; // Define how many races should be "current"
        $numberOfPastRaces = 5; // Define how many races should be "past"

        for ($i = 0; $i < 20; $i++) {
            $race = new Race();
            $race->setIdPublicRace($faker->uuid());
            $raceName = $faker->unique()->randomElement($raceNames);
            $race->setName($raceName);
            $raceNameParts = explode(' ', $raceName);
            $city = $raceNameParts[count($raceNameParts) - 1];
            $race->setDescription("Le " . $raceName . " est une course emblématique qui se déroule dans la magnifique ville de " . $city . ". Préparez-vous pour une expérience inoubliable !");

            if ($i < $numberOfCurrentRaces) {
                // Make these races "current" - started recently
                $race->setStartDate($faker->dateTimeBetween('-30 minutes', '-5 minutes'));
                $race->setIsFinished(false);
            } elseif ($i < $numberOfCurrentRaces + $numberOfPastRaces) {
                // Make these races "past" - started in the past and finished
                $race->setStartDate($faker->dateTimeBetween('-2 years', '-1 year'));
                $race->setIsFinished(true);
            } else {
                // Keep existing logic for other races (future)
                $race->setStartDate($faker->dateTimeBetween('+1 day', '+1 year'));
                $race->setIsFinished(false);
            }

            $race->setCreateAt($faker->dateTimeBetween('-5 years', '-2 years'));
            $race->setUpdatedAt($faker->dateTimeBetween($race->getCreateAt(), 'now'));
            $race->setKilometer(0.0); // Initialize kilometer to 0
            $race->setDistance($faker->randomFloat(2, 20, 200)); // Set a random distance for the race
            $manager->persist($race);
            $races[] = $race;
        }

        // Create 200 Runners
        $runners = [];
        for ($i = 0; $i < 200; $i++) {
            $runner = new Runner();
            $runner->setRace($faker->randomElement($races));
            $runner->setFirstName($faker->firstName());
            $runner->setLastName($faker->lastName());
            $runner->setNationality($faker->countryCode());
            $runner->setBibNumber($faker->unique()->randomNumber(4));
            $runner->setCreateAt($faker->dateTimeBetween('-5 years', '-2 years'));
            $runner->setUpdatedAt($faker->dateTimeBetween($runner->getCreateAt()->format('Y-m-d H:i:s'), 'now'));
            $manager->persist($runner);
            $runners[] = $runner;
        }

        // Create Results for each Runner in each Race
        foreach ($races as $index => $race) {
            $raceResults = [];
            $raceRunners = $faker->randomElements($runners, $faker->numberBetween(10, 30));

            foreach ($raceRunners as $runner) {
                $result = new Result();
                $result->setRace($race);
                $result->setRunner($runner);

                $hasFinished = $faker->boolean(80); // Default 80% chance of finishing

                // For races explicitly marked as finished (past races), all runners should be finished
                if ($race->isFinished()) {
                    $hasFinished = true;
                } elseif ($index < $numberOfCurrentRaces) {
                    // For "current" races, ensure more unfinished runners (e.g., 20% chance of finishing)
                    $hasFinished = $faker->boolean(20);
                }

                $result->setHasFinished($hasFinished);

                // Calculate time per km between 3:00 and 7:00
                $minutesPerKm = $faker->numberBetween(3, 7);
                $secondsPerKm = $faker->numberBetween(0, 59);
                $totalSecondsPerKm = ($minutesPerKm * 60) + $secondsPerKm;

                // Calculate total time in seconds
                $totalRaceSeconds = $totalSecondsPerKm * $race->getDistance(); // Use race distance for total time calculation

                if ($hasFinished) {
                    // Convert total seconds to H:i:s format
                    $hours = floor($totalRaceSeconds / 3600);
                    $minutes = floor(($totalRaceSeconds / 60) % 60);
                    $seconds = $totalRaceSeconds % 60;
                    $timeString = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                    $result->setTime($timeString);
                    $result->setLiveKilometer($race->getDistance());
                } else {
                    // For non-finishers, set a time that is clearly higher than any finisher
                    // This simulates them still being on the course or having a very long time
                    $extendedTotalSeconds = $totalRaceSeconds * $faker->randomFloat(2, 0.5, 1.5); // 50% to 150% of expected time
                    $hours = floor($extendedTotalSeconds / 3600);
                    $minutes = floor(($extendedTotalSeconds / 60) % 60);
                    $seconds = $extendedTotalSeconds % 60;
                    $timeString = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
                    $result->setTime($timeString);

                    // Calculate liveKilometer for unfinished runners
                    // Assuming a constant speed for simplicity in fixtures
                    $averageSpeedKmh = 60 / $totalSecondsPerKm; // km/h
                    $timeInHours = $extendedTotalSeconds / 3600;
                    $liveKm = $averageSpeedKmh * $timeInHours;
                    $result->setLiveKilometer(min($liveKm, $race->getDistance())); // Ensure it doesn't exceed race distance
                }

                $resultCreateAt = $faker->dateTimeBetween($race->getStartDate() < new \DateTime() ? $race->getStartDate() : 'now', 'now');
                $result->setCreateAt($resultCreateAt);
                $result->setUpdatedAt($faker->dateTimeBetween($result->getCreateAt(), 'now'));

                $raceResults[] = $result;
            }

            // Ensure at least one runner is unfinished for "current" races if all somehow finished
            if ($index < $numberOfCurrentRaces) {
                $allFinished = true;
                foreach ($raceResults as $result) {
                    if (!$result->isHasFinished()) {
                        $allFinished = false;
                        break;
                    }
                }
                if ($allFinished && !empty($raceResults)) {
                    // If all runners are finished in a current race, randomly pick one and make them unfinished
                    $randomResult = $faker->randomElement($raceResults);
                    $randomResult->setHasFinished(false);
                    $randomResult->setLiveKilometer($faker->randomFloat(2, 0, $race->getDistance() * 0.9)); // Set a liveKilometer less than race distance
                }
            }

            // Sort results by time to determine rank
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

            // Assign ranks based on sorted order
            foreach ($raceResults as $index => $result) {
                $result->setRunnerRank($index + 1);
                $manager->persist($result);
            }

            // After generating all results for a race, determine its overall status and set kilometer
            if ($race->getStartDate() <= new \DateTime()) { // Past or current race
                $hasUnfinishedRunners = false;
                foreach ($raceResults as $result) {
                    if (!$result->isHasFinished()) {
                        $hasUnfinishedRunners = true;
                        break;
                    }
                }

                if ($hasUnfinishedRunners) { // Current race
                    // Set race kilometer to the max liveKilometer of unfinished runners
                    $maxLiveKilometer = 0.0;
                    foreach ($raceResults as $result) {
                        if (!$result->isHasFinished() && $result->getLiveKilometer() > $maxLiveKilometer) {
                            $maxLiveKilometer = $result->getLiveKilometer();
                        }
                    }
                    $race->setKilometer($maxLiveKilometer);
                } else { // Finished race
                    $race->setKilometer($race->getDistance());
                }
            }
        }

        $manager->flush();
    }
}
