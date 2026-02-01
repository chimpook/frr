<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/users')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Route('', name: 'users_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 10)));

        $users = $this->userRepository->findPaginated($page, $limit);
        $total = $this->userRepository->countAll();

        $data = array_map(fn(User $u) => $u->toArray(), $users);

        return $this->json([
            'data' => $data,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    #[Route('/{id}', name: 'users_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($user->toArray());
    }

    #[Route('', name: 'users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Check for required password
        if (empty($data['password'])) {
            return $this->json(['errors' => ['password' => 'Password is required']], Response::HTTP_BAD_REQUEST);
        }

        // Check for duplicate email
        if (!empty($data['email']) && $this->userRepository->findOneByEmail($data['email'])) {
            return $this->json(['errors' => ['email' => 'This email is already registered']], Response::HTTP_BAD_REQUEST);
        }

        $user = new User();
        $user->setEmail($data['email'] ?? '');
        $user->setName($data['name'] ?? '');
        $user->setActive($data['active'] ?? true);

        // Set roles
        $roles = $data['roles'] ?? [];
        if (in_array('ROLE_ADMIN', $roles)) {
            $user->setRoles(['ROLE_ADMIN']);
        } else {
            $user->setRoles([]);
        }

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $errors = $this->validator->validate($user);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->userRepository->save($user, true);

        return $this->json($user->toArray(), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'users_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Check for duplicate email if email is being changed
        if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
            if ($this->userRepository->findOneByEmail($data['email'])) {
                return $this->json(['errors' => ['email' => 'This email is already registered']], Response::HTTP_BAD_REQUEST);
            }
            $user->setEmail($data['email']);
        }

        if (isset($data['name'])) {
            $user->setName($data['name']);
        }

        if (isset($data['active'])) {
            $user->setActive((bool) $data['active']);
        }

        if (isset($data['roles'])) {
            $roles = $data['roles'];
            if (in_array('ROLE_ADMIN', $roles)) {
                $user->setRoles(['ROLE_ADMIN']);
            } else {
                $user->setRoles([]);
            }
        }

        // Update password if provided
        if (!empty($data['password'])) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        $errors = $this->validator->validate($user);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json($user->toArray());
    }

    #[Route('/{id}', name: 'users_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // Prevent deleting yourself
        $currentUser = $this->getUser();
        if ($currentUser && $currentUser->getUserIdentifier() === $user->getEmail()) {
            return $this->json(['error' => 'Cannot delete your own account'], Response::HTTP_BAD_REQUEST);
        }

        $this->userRepository->remove($user, true);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
