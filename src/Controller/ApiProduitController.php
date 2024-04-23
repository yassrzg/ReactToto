<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Produit;
use App\Repository\BookRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;




class ApiProduitController extends AbstractController
{
    #[Route('/api/getProduct', name: 'product', methods: ['GET'])]
    public function getAllBooks(ProduitRepository $produitRepository, SerializerInterface $serializer): JsonResponse
    {
        $produitList = $produitRepository->findAll();

        $jsonProduitList = $serializer->serialize($produitList, 'json');
        return new JsonResponse($jsonProduitList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/produits', name: 'createProduit', methods: ['POST'])]
    public function createProduit(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator): JsonResponse
    {
        $data = $request->getContent();

        // Désérialisation des données JSON en objet Produit
        $produit = $serializer->deserialize($data, Produit::class, 'json');
        // Validation de l'objet Produit
        $errors = $validator->validate($produit);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()][] = $error->getMessage();
            }
            return new JsonResponse($errorMessages, Response::HTTP_BAD_REQUEST);
        }

        // Persiste l'objet Produit
        $entityManager->persist($produit);
        $entityManager->flush();

        // Réponse JSON avec l'objet Produit créé
        $jsonProduit = $serializer->serialize($produit, 'json');

        return new JsonResponse($jsonProduit, Response::HTTP_CREATED);
    }


    #[Route('/api/deleteProduits/{id}', name: 'deleteProduit', methods: ['DELETE'])]
    public function deleteProduit(Produit $produit, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($produit);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/updateProduits/{id}', name: 'updateProduit', methods: ['PUT'])]
    public function updateProduit(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, Produit $produit): JsonResponse {
        // Désérialiser les nouvelles données du produit en utilisant le serializer
        $updatedProduit = $serializer->deserialize(
            $request->getContent(),
            Produit::class,
            'json',
            [AbstractNormalizer::OBJECT_TO_POPULATE => $produit]
        );

        // Mettez à jour les propriétés du produit avec les nouvelles données
        $produit->setTitle($updatedProduit->getTitle());
        $produit->setImage($updatedProduit->getImage());
        $produit->setPrix($updatedProduit->getPrix());
        $produit->setDescription($updatedProduit->getDescription());

        // Persistez les modifications dans la base de données
        $em->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }






}
