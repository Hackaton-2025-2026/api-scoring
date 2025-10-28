<?php

namespace App\Controller;

use App\Entity\Result;
use App\Entity\Runner;
use App\Repository\ResultRepository;
use App\Repository\RunnerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

#[Route('/api/runners')]
#[OA\Tag(name: 'Runners')]
class RunnerController extends AbstractController
{
    public function __construct(
        private RunnerRepository $runnerRepository,
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
            items: new OA\Items(ref: new Model(type: Runner::class, groups: ['runner:read']))
        )
    )]
    public function getRunners(): JsonResponse
    {
        $runners = $this->runnerRepository->findAll();
        $data = $this->serializer->serialize($runners, 'json', ['groups' => 'runner:read']);
        return new JsonResponse($data, json: true);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new Model(type: Runner::class, groups: ['runner:read'])
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The ID of the runner', schema: new OA\Schema(type: 'integer'))]
    public function getRunner(Runner $runner): JsonResponse
    {
        $data = $this->serializer->serialize($runner, 'json', ['groups' => 'runner:read']);
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
    #[OA\Parameter(name: 'id', in: 'path', description: 'The ID of the runner', schema: new OA\Schema(type: 'integer'))]
    public function getRunnerResults(Runner $runner): JsonResponse
    {
        $results = $this->resultRepository->findByRunner($runner->getId());
        $data = $this->serializer->serialize($results, 'json', ['groups' => 'result:read']);
        return new JsonResponse($data, json: true);
    }
}
