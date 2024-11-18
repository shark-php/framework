<?php

namespace Shark\Http\Exceptions\Model;

use JetBrains\PhpStorm\ArrayShape;

final class ErrorModel implements \JsonSerializable {

    public string $title;

    public array $message;

    private int $status;

    public function __construct(string $title ,$message = [],int $status = 0)
    {
        $this->title = $title;
        $this->message = $message;
        $this->status = $status;
    }


    /**
     * @return array{title: , errors: array|mixed}
     */
    #[ArrayShape(["title" => "", "errors" => "array|mixed"])]
    public static function error($title , $message = []): array
    {
        return [
            "title" => $title,
            "errors" => $message
        ];
    }

    /**
     * @return array{title: string, errors: array}
     */
    #[ArrayShape(["title" => "string", "errors" => "array"])]
    public function toArray(): array
    {
        return [
            "title"     => $this->title,
            "errors"    => $this->message
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function __toString(): string
    {
        return json_encode($this->toArray());
    }
}