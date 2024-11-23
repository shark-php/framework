<?php
namespace Shark\Http\Middlewares;

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use React\Promise\PromiseInterface;
use Shark\Filesystem\Contracts\FilesystemInterface;
use Shark\Filesystem\Entities\File;
use Shark\Filesystem\Exceptions\FileNotFoundException;
use function React\Async\await;

class StaticFileMiddleware
{
    public function __construct(
        private readonly FilesystemInterface $filesystem
    )
    {
    }

    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        /**
         * @var ?File $file
         */
        try {
            $file = await($this->filesystem->get($request->getUri()->getPath()));
            if ($file)
                return new Response(200, ['Content-Type' => $file->mimeType],$file->contents);

        } catch (FileNotFoundException $e) {}

        return $next($request);
    }
}