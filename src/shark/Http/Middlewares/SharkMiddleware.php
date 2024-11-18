<?php

namespace Shark\Http\Middlewares;

use React\Http\Message\Response;
use React\Http\Message\ServerRequest;
use React\Promise\PromiseInterface;
use React\Socket\SocketServer;
use Respect\Validation\Exceptions\NestedValidationException;
use Shark\Http\Exceptions\AuthorizationException;
use Shark\Http\Exceptions\ForbiddenException;
use Shark\Http\Exceptions\MethodNotAllowedException;
use Shark\Http\Exceptions\Model\ErrorModel;
use Shark\Http\Exceptions\NotFoundException;
use Shark\Http\Exceptions\ValidationException;
use Shark\Http\Request\Request;
use Throwable;
use function React\Async\await;


final class SharkMiddleware
{
    public function __construct(
        protected SocketServer $socket
    )
    {
    }

    public function __invoke(ServerRequest $request, callable $next)
    {
        if ($request->getHeaderLine("Content-type") === "application/json"){
            $request = $request->withParsedBody(
                json_decode($request->getBody()->getContents(),true)
            );
        }
        $request = new Request($request);

        try {
            $response = $next($request);
            if ($response instanceof PromiseInterface)
            {
                $break = await($response);
                $error = $this->detectException($break);
                if (!is_null($error)){
                    if ($request->getHeaderLine("Accept") === "application/json") {
                        return Response::json($error->jsonSerialize())->withStatus($error->getStatus());
                    }

                    if ($request->getHeaderLine("Accept") === "text/plain") {
                        return Response::plaintext($this->renderHtmlError($error))->withStatus($error->getStatus());
                    }

                    return Response::html($this->renderHtmlError($error))->withStatus($error->getStatus());
                }

                if (!$break instanceof Response){
                    $response = Response::plaintext($break);
                }
            }

            return $response;
        }
        catch (Throwable $exception)
        {
            $error = $this->detectException($exception);
            if ($request->getHeaderLine("Accept") === "application/json") {
                return Response::json($error)
                    ->withStatus($error->getStatus());
            }

            if ($request->getHeaderLine("Accept") === "text/plain") {
                return Response::plaintext($this->renderHtmlError($error))->withStatus($error->getStatus());
            }

            return Response::html($this->renderHtmlError($error))->withStatus($error->getStatus());
        }
    }

    private function parseErrors(array $errors): array
    {
        $temp = [];

        foreach($errors as $key => $error_message){
            if(is_array($error_message))
                foreach($error_message as $field => $message)
                {
                    $temp[] = $message;
                }            
            else
                $temp[] = $error_message;
        }
        return $temp;
    }

    public function detectException($throwable) : ?ErrorModel
    {
        $title = "Internal Server Error";
        $status = 500;

        if ($throwable instanceof NestedValidationException)
        {
            $title = "Validation Failed!";
            $status = 422;
            $message = $this->parseErrors($throwable->getMessages());
        }
        elseif ($throwable instanceof ValidationException)
        {
            $title = "Validation Failed!";
            $status = 422;
            $message = [$throwable->getMessage()];
        }
        elseif ($throwable instanceof MethodNotAllowedException ){
            $this->socket->emit("error" , [$throwable]);
            $title = "Method Not Allowed!";
            $status = 405;
            $message = ["not allowed"];
        }
        elseif ($throwable instanceof NotFoundException ) {
            $this->socket->emit("error" , [$throwable]);
            $title = "Not Found!";
            $status = 404;
            $message = ["Route/Data not found!"];
        }
        elseif ($throwable instanceof AuthorizationException ){
            $this->socket->emit("error" , [$throwable]);
            $title = "Unauthorized!";
            $status = 401;
            $message = [$throwable->getMessage()];
        }
        elseif ($throwable instanceof ForbiddenException ){
            $this->socket->emit("error" , [$throwable]);
            $title = "Forbidden error!";
            $status = 403;
            $message = [$throwable->getMessage()];
        }
        else if ($throwable instanceof Throwable){
            $this->socket->emit("error" , [$throwable]);
            $message = [$throwable->getMessage()];
        } else {
            return null;
        }

        return new ErrorModel($title, $message ,$status);
    }

    public function renderHtmlError(ErrorModel $error) : string
    {
        $htmlTemplate = file_get_contents(__DIR__ . "/../pages/error.html");

        return str_replace([
            '{:status}',
            '{:title}',
            '{:messages}',
            '{:url}',
        ],[
            $error->getStatus(),
            $error->title,
            implode(',',$error->message),
            '/'
        ],$htmlTemplate);
    }
}