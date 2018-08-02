<?php
namespace EngineWorks\DBAL\Exceptions;

use Throwable;

class QueryException extends \RuntimeException
{
    /** @var string */
    private $query;

    public function __construct(string $message = '', string $query = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->query = $query;
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
