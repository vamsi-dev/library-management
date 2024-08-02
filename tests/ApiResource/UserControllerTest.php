<?php

namespace App\Tests\ApiResource;

use App\Domain\Book\Book;
use App\Domain\User\User;
use App\Domain\Entity\Constants;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityRepository $repository;
    private EntityRepository $bookRepository;
    private string $path = Constants::PATH_TO_API_USER.'/';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->followRedirects(true);
        
        $manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $manager->getRepository(User::class);
        $this->bookRepository = $manager->getRepository(Book::class);
    }

    /**
     * @return void
     */
    public function testGetAllUsers(): void
    {
        $this->client->request(Constants::METHOD_GET, $this->path);

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testGetUserByIdWithInvalidId(): void
    {
        $this->client->request(Constants::METHOD_GET, sprintf('%s%s', $this->path, 'invalid-id'));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        $this->assertEquals(Constants::MSG_INVALID_ID, $responseContent[Constants::TYPE_MESSAGE]);
    }

    public function testGetUserById()
    {
        $user = $this->repository->findOneBy(['email.email' => 'user1@example.com']);

        $this->client->request(Constants::METHOD_GET, sprintf('%s%s', $this->path, $user->getId()));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testCreateUserWithMissingName(): void
    {
        $this->client->request(Constants::METHOD_POST, $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode([
            'email' => 'john.doe.new@example.com',
            'password' => 'password123'
        ], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        $this->assertEquals(Constants::MSG_USER_MANDATORY_FIELDS, $responseContent[Constants::TYPE_MESSAGE]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testCreateUserWithMissingEmail(): void
    {
        $this->client->request(Constants::METHOD_POST, $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['name' => 'John Doe', 'password' => 'password123'], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        $this->assertEquals(Constants::MSG_USER_MANDATORY_FIELDS, $responseContent[Constants::TYPE_MESSAGE]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testCreateUserWithMissingPassword(): void
    {
        $this->client->request(Constants::METHOD_POST, $this->path . 'new', [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['name' => 'John Doe', 'email' => 'john.doe.new@example.com'], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(Constants::MSG_USER_MANDATORY_FIELDS, $responseContent[Constants::TYPE_MESSAGE]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testCreateUser(): void
    {
        $this->client->request(Constants::METHOD_POST, $this->path . 'new', [], [], ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'John Doe', 'email' => 'john.doe.new@example.com', 'password' => 'password123'], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(Constants::MSG_USER_CREATED, $responseContent[Constants::TYPE_STATUS]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testUpdateUserWithMissingName(): void
    {
        $user = $this->repository->findOneBy(['email.email' => 'user1@example.com']);

        $this->client->request(Constants::METHOD_PUT, sprintf('%s%s', $this->path, $user->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['email' => 'john.smith.edit@example.com', 'password' => 'newpassword123'], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Name and email are required fields', $responseContent[Constants::TYPE_MESSAGE]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testUpdateUserWithMissingEmail(): void
    {
        $user = $this->repository->findOneBy(['email.email' => 'user1@example.com']);

        $this->client->request(Constants::METHOD_PUT, sprintf('%s%s', $this->path, $user->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['name' => 'John Smith', 'password' => 'newpassword123'], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Name and email are required fields', $responseContent[Constants::TYPE_MESSAGE]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testUpdateUser(): void
    {
        $user = $this->repository->findOneBy(['email.email' => 'user1@example.com']);

        $this->client->request(Constants::METHOD_PUT, sprintf('%s%s', $this->path, $user->getId()), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], json_encode(['name' => 'John Smith', 'email' => 'john.smith.edit@example.com', 'password' => 'newpassword123'
        ], JSON_THROW_ON_ERROR));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        $this->assertEquals('User updated!', $responseContent[Constants::TYPE_STATUS]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testDeleteUserWithInvalidId(): void
    {
        $invalidUserId = 999999;

        $this->client->request(Constants::MSG_USER_DELETED, sprintf('%s%s', $this->path, $invalidUserId));

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        $this->assertEquals('User not found.', $responseContent[Constants::TYPE_MESSAGE]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testDeleteUser(): void
    {
        $user = $this->repository->findOneBy(['email.email' => 'john.smith.edit@example.com']);

        $this->client->request(Constants::MSG_USER_DELETED, sprintf('%s%s', $this->path, $user->getId()));

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        $this->assertEquals(Constants::MSG_USER_DELETED, $responseContent[Constants::TYPE_MESSAGE]);

        $deletedUser = $this->repository->findOneBy(['email.email' => 'john.smith.edit@example.com']);
        $this->assertNull($deletedUser);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testBorrowBook(): void
    {
        $user = $this->repository->findOneBy(['email.email' => 'user2@example.com']);
        $book = $this->bookRepository->findOneBy([Constants::PROPERTY_TITLE=> 'Book Title 2']);

        $this->client->request(Constants::METHOD_POST, $this->path . $user->getId() . '/borrow/' . $book->getId());

        $this->assertEquals(Response::HTTP_CREATED, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Book borrowed!', $responseContent[Constants::TYPE_STATUS]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testReturnBook(): void
    {
        $user = $this->repository->findOneBy(['email.email' => 'user2@example.com']);
        $book = $this->bookRepository->findOneBy([Constants::PROPERTY_TITLE=> 'Book Title 2']);
    
        $this->client->request(Constants::METHOD_POST, $this->path . $user->getId() . '/return/' . $book->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json'
        ]);
    
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        $this->assertEquals('Book returned!', $responseContent[Constants::TYPE_STATUS]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testBorrowBookWithInvalidUser(): void
    {
        $this->client->request(Constants::METHOD_POST, $this->path . '999/borrow/1'); // Invalid user ID

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        $this->assertEquals('User not found.', $responseContent[Constants::TYPE_MESSAGE]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testBorrowBookWithInvalidBook(): void
    {
        $user = $this->repository->findOneBy(['email.email' => 'user2@example.com']);

        $this->client->request(Constants::METHOD_POST, $this->path . $user->getId() . '/borrow/999');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        $this->assertEquals(Constants::MSG_BOOK_NOT_FOUND, $responseContent[Constants::TYPE_MESSAGE]);
    }

    public function testReturnBookWithInvalidUser()
    {
        $this->client->request(Constants::METHOD_POST, $this->path . '999/return/1');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('User not found.', $responseContent[Constants::TYPE_MESSAGE]);
    }

    /**
     * @return void
     * @throws \JsonException
     */
    public function testReturnBookWithInvalidBook(): void
    {
        $user = $this->repository->findOneBy(['email.email' => 'user2@example.com']);

        $this->client->request(Constants::METHOD_POST, $this->path . $user->getId() . '/return/999');

        $this->assertEquals(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
        $responseContent = json_decode($this->client->getResponse()->getContent(), true, Constants::HTTP_STATUS_512, JSON_THROW_ON_ERROR);
        $this->assertEquals(Constants::MSG_BOOK_NOT_FOUND, $responseContent[Constants::TYPE_MESSAGE]);
    }

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

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->restoreExceptionHandler();
    }
}
