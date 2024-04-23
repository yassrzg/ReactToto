<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

class ApiLoginController extends AbstractController
{

    private EntityManagerInterface $entityManager;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $entityManager, SerializerInterface $serializer) {
        $this->entityManager = $entityManager;
        $this->serializer = $serializer;

    }

    #[Route('/api/status', name: 'app_api_status')]
    public function status(): Response
    {



        return $this->json([
            'status'  => 'ok',
//            'token' => $token,
        ]);
    }

    #[Route('/api/login', name: 'app_api_login')]
    public function index(#[CurrentUser] ?User $user): Response
    {
        if (null === $user) {
            return $this->json([
                'message' => 'missing credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }
//        $token = ...; // somehow create an API token for $user

        return $this->json([
            'user'  => $user->getUserIdentifier(),
//            'token' => $token,
        ]);
    }


    #[Route('/api/register', name: 'app_api_register', methods: ['POST'])]
    public function register(Request $request, JWTTokenManagerInterface $jwtManager, UserPasswordHasherInterface $hashPassword): JsonResponse
    {
        $content = json_decode($request->getContent(), true);
        $newUser = new User();

        if ($content !== null) {
            $userExist = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $content['email']]);
            if (!$userExist) {
                $newUser->setEmail($content['email']);
                $newUser->setRoles(['ROLE_USER']);
                $password = $hashPassword->hashPassword($newUser,$content['password']);
                $newUser->setPassword($password);

                $this->entityManager->persist($newUser);
                $this->entityManager->flush();

//                // Générer le jeton d'authentification pour $newUser
//                $token = $jwtManager->create($newUser);

                return new JsonResponse(['message' => 'Opération réussie'], 200);
            }
        }

        return new JsonResponse(['message' => 'Erreur dans les données fournies'], 400);
    }

    #[Route('/api/getUser', name: 'app_api_getUser')]
    public function getUserData(SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();

        if ($user ) {

            $jsonUser = $serializer->serialize($user, 'json');

            return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);

        }

        return new JsonResponse(['message' => 'Erreur dans les données fournies'], 400);
    }


}
