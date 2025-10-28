<?php

namespace App\Controller;

use App\Entity\Result;
use App\Repository\ResultRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;

#[Route('/api/results')]
#[OA\Tag(name: 'Results')]
class ResultController extends AbstractController
{
    public function __construct(
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
            items: new OA\Items(ref: new Model(type: Result::class, groups: ['result:read']))
        )
    )]
    public function getResults(): JsonResponse
    {
        $results = $this->resultRepository->findAll();
        $data = $this->serializer->serialize($results, 'json', ['groups' => 'result:read']);
        return new JsonResponse($data, json: true);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Successful response',
        content: new Model(type: Result::class, groups: ['result:read'])
    )]
    #[OA\Parameter(name: 'id', in: 'path', description: 'The ID of the result', schema: new OA\Schema(type: 'integer'))]
    public function getResult(Result $result): JsonResponse
    {
        $data = $this->serializer->serialize($result, 'json', ['groups' => 'result:read']);
        return new JsonResponse($data, json: true);
    }
}
