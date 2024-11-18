<?php
namespace Shark\Http\Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use React\Promise\PromiseInterface;
use Shark\Filesystem\Entities\File;

class StaticFileMiddleware
{
    public function __construct()
    {
    }

    public function __invoke(ServerRequestInterface $request,string $file): PromiseInterface
    {
        return $this->query->Execute($request->getUri()->getPath())
            ->then(
                function (?File $file) {
                    if($file)
                        return Helpers::response($file->contents,200, ['Content-Type' => $file->mimeType]);
                    return JsonResponse::notFound("Route not found!");
                }
            )->otherwise(
                function (\Exception $exception) {
                    return Helpers::response($exception->getMessage(),500);
                }
            );
    }
}