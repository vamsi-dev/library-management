<?php

namespace App\Tests\ApiResource;

use Faker\Factory;
use App\Domain\Book\Book;
use App\Domain\Entity\Constants;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityRepository $repository;
    private string $path = Constants::PATH_TO_API_BOOK;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects(true);
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $manager->getRepository(Book::class);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testGetAllBooks(): void
    {
        $this->client->request('GET', $this->path);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($responseContent);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testGetBookByIdWithInvalidId(): void
    {
        $this->client->request('GET', sprintf('%s%s', $this->path, 'invalid-id'));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Invalid ID type. ID must be a positive integer.', $responseContent['message']);
    }

    /**
     * @return void
     */
    public function testGetBookById(): void
    {
        $book = $this->repository->findOneBy(['title'=> 'Book Title 1']);

        $this->client->request('GET', sprintf('%s%s', $this->path, $book->getId()));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testCreateBookWithMissingTitle(): void
    {
        $faker = Factory::create();

        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'author' => 'Test Author',
            'isbn' => $faker->isbn10()
        ], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Title, author, and ISBN are required fields', $responseContent['message']);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testCreateBookWithMissingAuthor(): void
    {
        $faker = Factory::create();

        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Test Book',
            'isbn' => $faker->isbn10()
        ], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Title, author, and ISBN are required fields', $responseContent['message']);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testCreateBookWithMissingIsbn(): void
    {
        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'New Title',
            'author' => 'New Author'
        ], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Title, author, and ISBN are required fields', $responseContent['message']);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testCreateBookWithInvalidIsbn(): void
    {
        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => 'invalidisbn'
        ], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('ISBN value is not valid.', $responseContent['message']);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testCreateBook(): void
    {
        $faker = Factory::create();

        $this->client->request('POST', $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'Test Book',
            'author' => 'Test Author',
            'isbn' => $faker->isbn10()
        ], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Book created!', $responseContent['status']);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testUpdateBookWithMissingTitle(): void
    {
        $faker = Factory::create();

        $book = $this->repository->findOneBy(['title' => 'Book Title 1']);

        $this->client->request('PUT', sprintf('%s%s', $this->path, $book->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'author' => 'New Author',
            'isbn' => $faker->isbn10()
        ], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Title, author, and ISBN are required fields', $responseContent['message']);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testUpdateBookWithMissingAuthor(): void
    {
        $faker = Factory::create();

        $book = $this->repository->findOneBy(['title' => 'Book Title 1']);

        $this->client->request('PUT', sprintf('%s%s', $this->path, $book->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['title' => 'New Title', 'isbn' => $faker->isbn10()], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Title, author, and ISBN are required fields', $responseContent['message']);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testUpdateBookWithMissingIsbn(): void
    {
        $book = $this->repository->findOneBy(['title' => 'Book Title 1']);

        $this->client->request('PUT', sprintf('%s%s', $this->path, $book->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['title' => 'New Title', 'author' => 'New Author'], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Title, author, and ISBN are required fields', $responseContent['message']);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testUpdateBookWithInvalidIsbn(): void
    {
        $book = $this->repository->findOneBy(['title' => 'Book Title 1']);

        $this->client->request('PUT', sprintf('%s%s', $this->path, $book->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'title' => 'New Title',
            'author' => 'New Author',
            'isbn' => 'invalid isbn'
        ], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('ISBN value is not valid.', $responseContent['message']);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testUpdateBookNotFound(): void
    {
        $faker = Factory::create();

        $invalidBookId = 999999;

        $this->client->request('PUT', sprintf('%s%s', $this->path, $invalidBookId), [], [], [
            'CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'New Title', 'author' => 'New Author', 'isbn' => $faker->isbn10()],
                JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Book not found.', $responseContent['message']);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testUpdateBook(): void
    {
        $faker = Factory::create();
        $book = $this->repository->findOneBy(['title'=> 'Book Title 1']);

        $this->client->request('PUT', sprintf('%s%s', $this->path, $book->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['title' => 'Updated Test Book', 'author' => 'Updated Test Author', 'isbn' => $faker->isbn10()
        ], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Book updated!', $responseContent['status']);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testDeleteBookNotFound(): void
    {
        $invalidBookId = 99999999999;

        $this->client->request('DELETE', sprintf('%s%s', $this->path, $invalidBookId));

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Book not found.', $responseContent['message']);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testDeleteBook()
    {
        $book = $this->repository->findOneBy(['title'=> 'Updated Test Book']);

        $this->client->request('DELETE', sprintf('%s%s', $this->path, $book->getId()));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Book deleted!.', $responseContent['message']);

        $deletedBook = $this->repository->findOneBy(['title'=> 'Updated Test Book']);
        $this->assertNull($deletedBook);
    }

    /**
     * @return void
     */
    protected function restoreExceptionHandler(): void
    {
        while (true) {
            $previousHandler = set_exception_handler(static fn() => null);
            restore_exception_handler();

            if ($previousHandler === null) {
                break;
            }

            restore_exception_handler();
        }
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->restoreExceptionHandler();
    }
}
