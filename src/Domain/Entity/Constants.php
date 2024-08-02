<?php

namespace App\Domain\Entity;

class Constants
{
    /** HTTP Status Codes */
    public const HTTP_STATUS_200 = 200;
    public const HTTP_STATUS_201 = 201;
    public const HTTP_STATUS_204 = 204;
    public const HTTP_STATUS_400 = 400;
    public const HTTP_STATUS_401 = 401;
    public const HTTP_STATUS_403 = 403;
    public const HTTP_STATUS_404 = 404;
    public const HTTP_STATUS_405 = 405;
    public const HTTP_STATUS_406 = 406;
    public const HTTP_STATUS_409 = 409;
    public const HTTP_STATUS_500 = 500;
    public const HTTP_STATUS_503 = 503;
    public const HTTP_STATUS_504 = 504;
    public const HTTP_STATUS_505 = 505;
    public const HTTP_STATUS_512 = 512;

    /** API Paths */
    public const PATH_TO_API_BOOK = '/api/book';
    public const PATH_TO_API_USER = '/api/user';

    /** Type Constants */
    public const TYPE_ARRAY = 'array';
    public const TYPE_STATUS = 'status';
    public const TYPE_STRING = 'string';
    public const TYPE_MESSAGE = 'message';

    /** Method Constants */
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_DELETE = 'DELETE';

    /** Property Fields */
    public const PROPERTY_NAME = 'name';
    public const PROPERTY_ISBN = 'isbn';
    public const PROPERTY_TITLE = 'title';
    public const PROPERTY_EMAIL = 'email';
    public const PROPERTY_AUTHOR = 'author';
    public const PROPERTY_PASSWORD = 'password';


    /** Common Messages */
    public const MSG_INVALID_ID = 'Invalid ID';
    public const MSG_INVALID_DATA = 'Invalid data';
    public const MSG_DATA_NOT_FOUND = 'Data not found';
    public const MSG_EMAIL_ALREADY_EXISTS = 'Email already exists';
    public const MSG_FOREIGN_KEY_VIOLATION = 'Foreign key constraint violation';
    public const MSG_NORMAL_ERROR_TRY_AGAIN = 'An error occurred. Please try again!';

    /** Book Messages */
    public const MSG_BOOK_NOT_FOUND = 'Book not found';
    public const MSG_BOOK_CREATED = 'Book created successfully';
    public const MSG_BOOK_UPDATED = 'Book updated successfully';
    public const MSG_BOOK_DELETED = 'Book deleted successfully';
    public const MSG_BOOK_BORROWED = 'Book borrowed successfully';
    public const MSG_BOOK_RETURNED = 'Book returned successfully';
    public const MSG_BOOK_MANDATORY_FIELDS = 'Title, Author and ISBN are mandatory fields';
    public const MSG_UNABLE_TO_FETCH_BOOKS = 'Unable to fetching the books. Please try again';
    public const MSG_NO_BORROW_OR_RETURNED = 'Book is not borrowed or has been returned already';
    public const MSG_ISBN_ALREADY_EXISTS = 'ISBN number is already associated with a existing book';

    /** User Messages */
    public const MSG_USER_NOT_FOUND = 'User not found';
    public const MSG_USER_DELETED = 'User deleted successfully';
    public const MSG_USER_CREATED = 'User created successfully';
    public const MSG_USER_UPDATED = 'User updated successfully';
    public const MSG_USER_MANDATORY_FIELDS = 'Name, email and password are mandatory fields';
    public const MSG_MAXIMUM_BOOKS_BORROWED = 'User has reached the maximum number of books borrowed';
}