<?php

declare(strict_types=1);

namespace App\Support;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;

/**
 * Accès statique au logger Monolog.
 */
final class Log
{
    private static ?Logger $logger = null;

    public static function instance(): Logger
    {
        if (self::$logger !== null) {
            return self::$logger;
        }

        $logger = new Logger('senebridge');
        $level = config('app.debug', false) ? Level::Debug : Level::Info;

        $handler = new RotatingFileHandler(storage_path('logs/app.log'), 14, $level);
        $handler->setFormatter(new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s'
        ));

        $logger->pushHandler($handler);

        return self::$logger = $logger;
    }

    public static function debug(string $message, array $context = []): void
    {
        self::instance()->debug($message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::instance()->info($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::instance()->warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::instance()->error($message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::instance()->critical($message, $context);
    }
}