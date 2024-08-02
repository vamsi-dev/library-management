<?php

namespace App\ApiResource;

use Exception;
use App\Domain\User\User;
use OpenApi\Attributes as SWG;
use App\Domain\ValueObject\Name;
use App\Domain\Entity\Constants;
use App\Domain\User\UserService;
use App\Domain\ValueObject\Email;
use App\Domain\User\UserRepository;
use App\Domain\ValueObject\Password;
use App\Exception\BookNotFoundException;
use App\Exception\UserNotFoundException;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[Route(Constants::PATH_TO_API_USER)]
class UserController extends AbstractController
{
    private UserService $userService;
    private UserPasswordHasherInterface $passwordHash;

    /**
     * @param UserService $userService
     * @param UserPasswordHasherInterface $passwordHash
     */
    public function __construct(UserService $userService, UserPasswordHasherInterface $passwordHash)
    {
        $this->userService = $userService;
        $this->passwordHash = $passwordHash;
    }

    /**
     * @param UserRepository $userRepository
     * @return Response
     */
    #[SWG\Get(
        summary: "Get all users",
        responses: [
            new SWG\Response(
                response: Constants::HTTP_STATUS_200,
                description: "List of users",
                content: new SWG\JsonContent(type: Constants::TYPE_ARRAY, items: new SWG\Items(ref: new Model(type: User::class)))
            )
        ]
    )]
    #[Route('', methods: [Constants::METHOD_GET])]
    public function getUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->json($users, Constants::HTTP_STATUS_200, [], ['groups' => ['user']]);
    }

    /**
     * @param $id
     * @param UserRepository $userRepository
     * @return Response
     */
    #[SWG\Get(
        summary: "Get a user by ID",
        parameters: [
            new SWG\Parameter(name: "id", description: "User ID", in: "path", required: true, schema: new SWG\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new SWG\Response(response: Constants::HTTP_STATUS_200, description: "", content: new SWG\JsonContent(ref: new Model(type: User::class))),
            new SWG\Response(response: Constants::HTTP_STATUS_400, description: Constants::MSG_INVALID_ID),
            new SWG\Response(response: Constants::HTTP_STATUS_404, description: Constants::MSG_USER_NOT_FOUND)
        ]
    )]
    #[Route('/{id}', methods: [Constants::METHOD_GET])]
    public function getUserById($id, UserRepository $userRepository): Response
    {
        $intId = (int) $id;
        if (!is_numeric($id) || $intId <= 0) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_INVALID_ID], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_USER_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        if ($user->isDeleted()) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_USER_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        return $this->json($user, Constants::HTTP_STATUS_200, [], ['groups' => ['user']]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \JsonException
     */
    #[SWG\Post(
        summary: "Create a new user",
        requestBody: new SWG\RequestBody(
            required: true,
            content: new SWG\JsonContent(
                properties: [
                    new SWG\Property(property: Constants::PROPERTY_NAME, type: Constants::TYPE_STRING, example: "user name"),
                    new SWG\Property(property: Constants::PROPERTY_EMAIL, type: Constants::TYPE_STRING, example: "user@example.com"),
                    new SWG\Property(property: Constants::PROPERTY_PASSWORD, type: Constants::TYPE_STRING, example: "password123")
                ],
                type: "object"
            )
        ),
        responses: [
            new SWG\Response(response: Constants::HTTP_STATUS_201, description: Constants::MSG_USER_CREATED),
            new SWG\Response(response: Constants::HTTP_STATUS_400, description: Constants::MSG_INVALID_DATA),
            new SWG\Response(response: Constants::HTTP_STATUS_406, description: Constants::MSG_USER_MANDATORY_FIELDS),
            new SWG\Response(response: Constants::HTTP_STATUS_409, description: Constants::MSG_EMAIL_ALREADY_EXISTS),
            new SWG\Response(response: Constants::HTTP_STATUS_500, description: Constants::MSG_NORMAL_ERROR_TRY_AGAIN)
        ]
    )]
    #[Route('/new', methods: [Constants::METHOD_POST])]
    public function createUser(Request $request): Response
    {
        $data = json_decode($request->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        if (!$data) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_INVALID_DATA], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data[Constants::PROPERTY_NAME]) || empty($data[Constants::PROPERTY_EMAIL]) || empty($data[Constants::PROPERTY_PASSWORD])) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_USER_MANDATORY_FIELDS], Response::HTTP_NOT_ACCEPTABLE);
        }

        $hashedPassword = $this->passwordHash->hashPassword(new User(
            new Name($data[Constants::PROPERTY_NAME]),
            new Email($data[Constants::PROPERTY_EMAIL]),
            new Password($data[Constants::PROPERTY_PASSWORD])
        ), $data[Constants::PROPERTY_PASSWORD]);
        $user = $this->userService->createUser($data[Constants::PROPERTY_NAME], $data[Constants::PROPERTY_EMAIL], $hashedPassword);

        try {
            $this->userService->saveUser($user);
        } catch (UniqueConstraintViolationException $e) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_EMAIL_ALREADY_EXISTS], Response::HTTP_CONFLICT);
        } catch (Exception $e) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_NORMAL_ERROR_TRY_AGAIN], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([Constants::TYPE_STATUS => Constants::MSG_USER_CREATED], Response::HTTP_CREATED);
    }

    /**
     * @param Request $request
     * @param $id
     * @param UserRepository $userRepository
     * @return Response
     * @throws \JsonException
     */
    #[SWG\Put(
        summary: "Update an existing user",
        requestBody: new SWG\RequestBody(
            required: true,
            content: new SWG\JsonContent(
                properties: [
                    new SWG\Property(property: Constants::PROPERTY_NAME, type: Constants::TYPE_STRING, example: "user name"),
                    new SWG\Property(property: Constants::PROPERTY_EMAIL, type: Constants::TYPE_STRING, example: "user@example.com"),
                    new SWG\Property(property: Constants::PROPERTY_PASSWORD, type: Constants::TYPE_STRING, example: "password123")
                ],
                type: "object"
            )
        ),
        parameters: [
            new SWG\Parameter(name: "id", description: "User ID", in: "path", required: true, schema: new SWG\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new SWG\Response(response: Constants::HTTP_STATUS_200, description: Constants::MSG_USER_UPDATED),
            new SWG\Response(response: Constants::HTTP_STATUS_400, description: Constants::MSG_INVALID_ID),
            new SWG\Response(response: Constants::HTTP_STATUS_404, description: Constants::MSG_USER_NOT_FOUND),
            new SWG\Response(response: Constants::HTTP_STATUS_406, description: Constants::MSG_USER_MANDATORY_FIELDS),
            new SWG\Response(response: Constants::HTTP_STATUS_409, description: Constants::MSG_EMAIL_ALREADY_EXISTS),
            new SWG\Response(response: Constants::HTTP_STATUS_500, description: Constants::MSG_NORMAL_ERROR_TRY_AGAIN)
        ]
    )]
    #[Route('/{id}', methods: [Constants::METHOD_PUT])]
    public function updateUser(Request $request, $id, UserRepository $userRepository): Response
    {
        $intId = (int) $id;
        if (!is_numeric($id) || $intId <= 0) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_INVALID_ID], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        if (!$data) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_INVALID_DATA], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_USER_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        if (empty($data[Constants::PROPERTY_NAME]) || empty($data[Constants::PROPERTY_EMAIL])) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_USER_MANDATORY_FIELDS], Response::HTTP_NOT_ACCEPTABLE);
        }

        $hashedPassword = null;
        if (!empty($data[Constants::PROPERTY_PASSWORD])) {
            $hashedPassword = $this->passwordHash->hashPassword($user, $data[Constants::PROPERTY_PASSWORD]);
        }
        $updatedUser = $this->userService->updateUser($user, $data[Constants::PROPERTY_NAME], $data[Constants::PROPERTY_EMAIL], $hashedPassword);

        try {
            $this->userService->saveUser($updatedUser);
        } catch (UniqueConstraintViolationException $e) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_EMAIL_ALREADY_EXISTS], Response::HTTP_CONFLICT);
        } catch (Exception $e) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_NORMAL_ERROR_TRY_AGAIN], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([Constants::TYPE_STATUS => Constants::MSG_USER_UPDATED]);
    }

    /**
     * @param int $id
     * @param UserRepository $userRepository
     * @return Response
     */
    #[SWG\Delete(
        summary: "Delete a user",
        parameters: [
            new SWG\Parameter(name: "id", description: "User ID", in: "path", required: true, schema: new SWG\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new SWG\Response(response: Constants::HTTP_STATUS_200, description: Constants::MSG_USER_DELETED),
            new SWG\Response(response: Constants::HTTP_STATUS_404, description: Constants::MSG_USER_NOT_FOUND),
            new SWG\Response(response: Constants::HTTP_STATUS_400, description: Constants::MSG_FOREIGN_KEY_VIOLATION),
            new SWG\Response(response: Constants::HTTP_STATUS_500, description: Constants::MSG_NORMAL_ERROR_TRY_AGAIN),
        ]
    )]
    #[Route('/{id}', methods: [Constants::METHOD_DELETE])]
    public function deleteUser(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_USER_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->userService->deleteUser($user);
        } catch (Exception $e) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_NORMAL_ERROR_TRY_AGAIN], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([Constants::TYPE_STATUS => Constants::MSG_USER_DELETED]);
    }

    /**
     * @param int $user
     * @param int $book
     * @return Response
     */
    #[SWG\Post(
        summary: "Borrow a book",
        parameters: [
            new SWG\Parameter(name: "user", description: "User ID", in: "path", required: true, schema: new SWG\Schema(type: "integer"), example: 1),
            new SWG\Parameter(name: "book", description: "Book ID", in: "path", required: true, schema: new SWG\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new SWG\Response(response: Constants::HTTP_STATUS_204, description: Constants::MSG_BOOK_BORROWED),
            new SWG\Response(response: Constants::HTTP_STATUS_404, description: Constants::MSG_DATA_NOT_FOUND),
            new SWG\Response(response: Constants::HTTP_STATUS_400, description: Constants::MSG_MAXIMUM_BOOKS_BORROWED)
        ]
    )]
    #[Route('/{user}/borrow/{book}', name: 'borrow_book', methods: [Constants::METHOD_POST])]
    public function borrowBook(int $user, int $book): Response
    {
        try {
            $this->userService->borrowBook($user, $book);
            return $this->json([Constants::TYPE_STATUS => Constants::MSG_BOOK_BORROWED], Response::HTTP_CREATED);
        } catch (UserNotFoundException|BookNotFoundException $e) {
            return $this->json([Constants::TYPE_MESSAGE => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->json([Constants::TYPE_MESSAGE => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param int $user
     * @param int $book
     * @return Response
     */
    #[SWG\Post(
        summary: "Return a borrowed book",
        parameters: [
            new SWG\Parameter(name: "user", description: "User ID", in: "path", required: true, schema: new SWG\Schema(type: "integer"), example: 1),
            new SWG\Parameter(name: "book", description: "Book ID", in: "path", required: true, schema: new SWG\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new SWG\Response(response: Constants::HTTP_STATUS_204, description: Constants::MSG_BOOK_RETURNED),
            new SWG\Response(response: Constants::HTTP_STATUS_404, description: Constants::MSG_DATA_NOT_FOUND),
            new SWG\Response(response: Constants::HTTP_STATUS_400, description: Constants::MSG_NO_BORROW_OR_RETURNED)
        ]
    )]
    #[Route('/{user}/return/{book}', name: 'return_book', methods: [Constants::METHOD_POST])]
    public function returnBook(int $user, int $book): Response
    {
        try {
            $this->userService->returnBook($user, $book);
            return $this->json([Constants::TYPE_STATUS => Constants::MSG_BOOK_RETURNED]);
        } catch (UserNotFoundException|BookNotFoundException $e) {
            return $this->json([Constants::TYPE_MESSAGE => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->json([Constants::TYPE_MESSAGE => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}