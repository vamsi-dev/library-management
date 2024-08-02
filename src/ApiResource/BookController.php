<?php

namespace App\ApiResource;

use App\Domain\Book\Book;
use App\Domain\Book\BookRepository;
use App\Domain\Book\BookService;
use App\Domain\Entity\Constants;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as SWG;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


#[Route(Constants::PATH_TO_API_BOOK)]
class BookController extends AbstractController
{
    private BookService $bookService;

    public function __construct(BookService $bookService)
    {
        $this->bookService = $bookService;
    }

    /**
     * This method is used to get all books that are available
     * @param BookRepository $bookRepository
     * @return Response
     */
    #[SWG\Get(
        summary: "Get all books",
        responses: [
            new SWG\Response(
                response: Constants::HTTP_STATUS_200,
                description: "List of books",
                content: new SWG\JsonContent(type: Constants::TYPE_ARRAY, items: new SWG\Items(ref: new Model(type: Book::class)))
            )
        ]
    )]
    #[Route('', methods: [Constants::METHOD_GET])]
    public function getBooks(BookRepository $bookRepository): Response
    {
        try {
            $books = $bookRepository->findAll();

            return $this->json($books, Constants::HTTP_STATUS_200, [], ['groups' => ['book']]);
        } catch (\Exception $e) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_UNABLE_TO_FETCH_BOOKS], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * This method is used to get a specific book by {id}
     * @param $id
     * @param BookRepository $bookRepository
     * @return Response
     */
    #[SWG\Get(
        summary: "Get a book by ID",
        parameters: [
            new SWG\Parameter(name: "id", description: "Book ID", in: "path", required: true, schema: new SWG\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new SWG\Response(response: Constants::HTTP_STATUS_200, description: "", content: new SWG\JsonContent(ref: new Model(type: Book::class))),
            new SWG\Response(response: Constants::HTTP_STATUS_400, description: Constants::MSG_INVALID_ID),
            new SWG\Response(response: Constants::HTTP_STATUS_404, description: Constants::MSG_BOOK_NOT_FOUND)
        ]
    )]
    #[Route('/{id}', methods: [Constants::METHOD_GET])]
    public function getBookById($id, BookRepository $bookRepository): Response
    {
        $intId = (int) $id;
        if (!is_numeric($id) || $intId <= 0) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_INVALID_ID], Response::HTTP_BAD_REQUEST);
        }

        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_INVALID_ID], Response::HTTP_NOT_FOUND);
        }

        if ($book->isDeleted()) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_BOOK_DELETED], Response::HTTP_NOT_FOUND);
        }

        return $this->json($book, Constants::HTTP_STATUS_200, [], ['groups' => ['book']]);
    }

    /**
     * This method is used to create a new book
     * @param Request $request
     * @return Response
     * @throws \JsonException
     */
    #[SWG\Post(
        summary: "Create New Book",
        requestBody: new SWG\RequestBody(
            required: true,
            content: new SWG\JsonContent(
                properties: [
                    new SWG\Property(property: Constants::PROPERTY_TITLE, type: Constants::TYPE_STRING, example: "Book Title"),
                    new SWG\Property(property: Constants::PROPERTY_AUTHOR, type: Constants::TYPE_STRING, example: "Name of the Author"),
                    new SWG\Property(property: Constants::PROPERTY_ISBN, type: Constants::TYPE_STRING, example: "123-78-5562")
                ],
                type: "object"
            )
        ),
        responses: [
            new SWG\Response(response: Constants::HTTP_STATUS_201, description: Constants::MSG_BOOK_CREATED),
            new SWG\Response(response: Constants::HTTP_STATUS_400, description: Constants::MSG_INVALID_DATA),
            new SWG\Response(response: Constants::HTTP_STATUS_406, description: Constants::MSG_BOOK_MANDATORY_FIELDS),
            new SWG\Response(response: Constants::HTTP_STATUS_409, description: Constants::MSG_ISBN_ALREADY_EXISTS),
        ]
    )]
    #[Route('/new', methods: [Constants::METHOD_POST])]
    public function createBook(Request $request): Response
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        if (!$data) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_INVALID_DATA], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data[Constants::PROPERTY_TITLE]) || empty($data[Constants::PROPERTY_AUTHOR]) || empty($data[Constants::PROPERTY_ISBN])) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_BOOK_MANDATORY_FIELDS], Response::HTTP_NOT_ACCEPTABLE);
        }

        $book = $this->bookService->createBook($data);

        try {
            $this->bookService->saveBook($book);
        } catch (UniqueConstraintViolationException $e) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_ISBN_ALREADY_EXISTS], Response::HTTP_CONFLICT);
        } catch (ForeignKeyConstraintViolationException $e) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_FOREIGN_KEY_VIOLATION], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([Constants::TYPE_MESSAGE => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([Constants::TYPE_STATUS => Constants::MSG_BOOK_CREATED], Response::HTTP_CREATED);
    }

    /**
     * This method is used to update a book's data by {id}
     * @param Request $request
     * @param int $id
     * @param BookRepository $bookRepository
     * @return Response
     * @throws \JsonException
     */
    #[SWG\Put(
        summary: "Update a book by id",
        requestBody: new SWG\RequestBody(
            required: true,
            content: new SWG\JsonContent(
                properties: [
                    new SWG\Property(property: Constants::PROPERTY_TITLE, type: Constants::TYPE_STRING, example: "Book Title"),
                    new SWG\Property(property: Constants::PROPERTY_AUTHOR, type: Constants::TYPE_STRING, example: "Author Name"),
                    new SWG\Property(property: Constants::PROPERTY_ISBN, type: Constants::TYPE_STRING, example: "123-78-5562")
                ],
                type: "object"
            )
        ),
        parameters: [
            new SWG\Parameter(name: "id", description: "Book ID", in: "path", required: true, schema: new SWG\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new SWG\Response(response: Constants::HTTP_STATUS_200, description: Constants::MSG_BOOK_UPDATED),
            new SWG\Response(response: Constants::HTTP_STATUS_400, description: Constants::MSG_INVALID_DATA),
            new SWG\Response(response: Constants::HTTP_STATUS_404, description: Constants::MSG_INVALID_ID),
            new SWG\Response(response: Constants::HTTP_STATUS_406, description: Constants::MSG_BOOK_MANDATORY_FIELDS),
            new SWG\Response(response: Constants::HTTP_STATUS_409, description: Constants::MSG_ISBN_ALREADY_EXISTS)
        ]
    )]
    #[Route('/{id}', methods: [Constants::METHOD_PUT])]
    public function updateBook(Request $request, int $id, BookRepository $bookRepository): Response
    {
        $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        if (!$data) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_INVALID_ID], Response::HTTP_BAD_REQUEST);
        }

        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_INVALID_ID], Response::HTTP_NOT_FOUND);
        }

        if (empty($data[Constants::PROPERTY_TITLE]) || empty($data[Constants::PROPERTY_AUTHOR]) || empty($data[Constants::PROPERTY_ISBN])) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_BOOK_MANDATORY_FIELDS], Response::HTTP_NOT_ACCEPTABLE);
        }

        $updatedBook = $this->bookService->updateBook($book, $data);

        try {
            $this->bookService->saveBook($updatedBook);
        } catch (UniqueConstraintViolationException $e) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_ISBN_ALREADY_EXISTS], Response::HTTP_CONFLICT);
        } catch (ForeignKeyConstraintViolationException $e) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_FOREIGN_KEY_VIOLATION], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json([Constants::TYPE_MESSAGE => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([Constants::TYPE_STATUS => Constants::MSG_BOOK_UPDATED]);
    }

    /**
     * This method is used to delete a book by {id}
     * @param int $id
     * @param BookRepository $bookRepository
     * @return Response
     */
    #[SWG\Delete(
        summary: "Delete a book",
        parameters: [
            new SWG\Parameter(name: "id", description: "Book ID", in: "path", required: true, schema: new SWG\Schema(type: "integer"), example: 1)
        ],
        responses: [
            new SWG\Response(response: Constants::HTTP_STATUS_200, description: Constants::MSG_BOOK_DELETED),
            new SWG\Response(response: Constants::HTTP_STATUS_404, description: Constants::MSG_BOOK_NOT_FOUND),
            new SWG\Response(response: Constants::HTTP_STATUS_400, description: Constants::MSG_FOREIGN_KEY_VIOLATION),
            new SWG\Response(response: 500, description: Constants::MSG_NORMAL_ERROR_TRY_AGAIN)
        ]
    )]
    #[Route('/{id}', methods: [Constants::METHOD_DELETE])]
    public function deleteBook(int $id, BookRepository $bookRepository): Response
    {
        $book = $bookRepository->find($id);
        if (!$book) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_BOOK_NOT_FOUND], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->bookService->deleteBook($book);
        } catch (\Exception $e) {
            return $this->json([Constants::TYPE_MESSAGE => Constants::MSG_NORMAL_ERROR_TRY_AGAIN], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([Constants::TYPE_STATUS => Constants::MSG_BOOK_DELETED]);
    }
}