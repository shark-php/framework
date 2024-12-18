<?php


namespace Shark\Logger;

use Bramus\Monolog\Formatter\ColoredLineFormatter;
use Monolog\Formatter\JsonFormatter;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\FirePHPHandler;
use Monolog\Handler\StreamHandler;
use Psr\Log\LoggerInterface;

class Factory
{
    const JSON_FORMATTER = "json";

    const COLOR_FORMATTER = "color";

    /**
     * Detailed debug information
     */
    const Debug = "Debug";

    /**
     * Interesting events
     *
     * Examples: User logs in, SQL logs.
     */
    const Info = "Info";

    /**
     * Uncommon events
     */
    const Notice = "Notice";

    /**
     * Exceptional occurrences that are not errors
     *
     * Examples: Use of deprecated APIs, poor use of an API,
     * undesirable things that are not necessarily wrong.
     */
    const Warning = "Warning";

    /**
     * Runtime errors
     */
    const Error = "Error";

    /**
     * Critical conditions
     *
     * Example: Application component unavailable, unexpected exception.
     */
    const Critical = "Critical";

    /**
     * Action must be taken immediately
     *
     * Example: Entire website down, database unavailable, etc.
     * This should trigger the SMS alerts and wake you up.
     */
    const Alert = "Alert";

    /**
     * Urgent alert.
     */
    const Emergency = "Emergency";

    /**
     * Create logger instance
     *
     * @param string $logger_path
     * @param string $app_name
     * @param string $level
     * @param bool $std
     * @param bool $file
     * @return LoggerInterface
     */
    public static function create(string $logger_path = "", string $app_name = "", string $level = self::Error,bool  $std = true, bool $file = false,string $formatter = self::JSON_FORMATTER ): LoggerInterface
    {
        $handler = [];
        if ($std){
            $stdHandler = new StreamHandler('php://output', Level::fromName($level));
            switch ($formatter){
                case self::COLOR_FORMATTER:
                    $stdHandler->setFormatter(new ColoredLineFormatter());
                    break;
                case self::JSON_FORMATTER:
                    $stdHandler->setFormatter(new JsonFormatter());
                    break;
            }

            $handler[] = $stdHandler;
        }

        if ($file)
            $handler[] =  new StreamHandler($logger_path, Level::fromName($level));
        $handler[] =   new FirePHPHandler();

        return new Logger($app_name, $handler);
    }
}