<?php

namespace App\Controller;

use App\Entity\Race;
use App\Entity\Result;
use App\Repository\RaceRepository;
use App\Repository\ResultRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

#[Route('/api/races')]
#[OA\Tag(name: 'Races')]
class RaceController extends AbstractController
{
    public function __construct(
        private RaceRepository $raceRepository,
        private ResultRepository $resultRepository,
        private SerializerInterface $serializer
    ) {
    }

    #[Route('', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Race::class, groups: ['race:read']))
        )
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        description: 'Filter races by status (past, current, future)',
        schema: new OA\Schema(type: 'string', enum: ['past', 'current', 'future'])
    )]
    #[OA\Parameter(
        name: 'sort',
        in: 'query',
        description: 'Sort races by date (date_asc, date_desc)',
        schema: new OA\Schema(type: 'string', enum: ['date_asc', 'date_desc'])
    )]
    public function getRaces(Request $request): JsonResponse
    {
        $status = $request->query->get('status');
        $sort = $request->query->get('sort');
        $races = $this->raceRepository->findByStatus($status, $sort);
        $data = $this->serializer->serialize($races, 'json', ['groups' => 'race:read']);
        return new JsonResponse($data, json: true);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new Model(type: Race::class, groups: ['race:read'])
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The ID of the race', schema: new OA\Schema(type: 'integer'))]
    public function getRace(Race $race): JsonResponse
    {
        $data = $this->serializer->serialize($race, 'json', ['groups' => 'race:read']);
        return new JsonResponse($data, json: true);
    }

    #[Route('/{id}/results', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Result::class, groups: ['result:read']))
        )
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The ID of the race', schema: new OA\Schema(type: 'integer'))]
    public function getRaceResults(Race $race): JsonResponse
    {
        $results = $this->resultRepository->findByRace($race->getId());
        $data = $this->serializer->serialize($results, 'json', ['groups' => 'result:read']);
        return new JsonResponse($data, json: true);
    }

    #[Route('/{id}/km', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'kilometer', type: 'number', format: 'float')
            ]
        )
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The ID of the race', schema: new OA\Schema(type: 'integer'))]
    public function getRaceKilometer(Race $race): JsonResponse
    {
        return new JsonResponse(['kilometer' => $race->getKilometer()]);
    }
}
