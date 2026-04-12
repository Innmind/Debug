<?php
declare(strict_types = 1);

namespace Innmind\Debug\Kernel;

use Innmind\Debug\{
    Recorder,
    Recorder\Beacon,
    Recorder\Exception,
    Http,
};
use Innmind\DI\Service;

/**
 * @internal
 * @template T of object
 * @implements Service<T>
 */
enum Services implements Service
{
    case beacon;
    case exception;
    case cliRecorder;
    case httpRecorder;
    case httpEnvironment;
    case httpException;
    case httpEndProfile;

    /**
     * @internal
     *
     * @return self<Beacon>
     */
    public static function beacon(): self
    {
        /** @var self<Beacon> */
        return self::beacon;
    }

    /**
     * @internal
     *
     * @return self<Exception>
     */
    public static function exception(): self
    {
        /** @var self<Exception> */
        return self::exception;
    }

    /**
     * @internal
     *
     * @return self<Recorder>
     */
    public static function cliRecorder(): self
    {
        /** @var self<Recorder> */
        return self::cliRecorder;
    }

    /**
     * @internal
     *
     * @return self<Recorder>
     */
    public static function httpRecorder(): self
    {
        /** @var self<Recorder> */
        return self::httpRecorder;
    }

    /**
     * @internal
     *
     * @return self<Http\EndProfile>
     */
    public static function httpEndProfile(): self
    {
        /** @var self<Http\EndProfile> */
        return self::httpEndProfile;
    }

    /**
     * @internal
     *
     * @return self<Http\RecordEnvironment>
     */
    public static function httpEnvironment(): self
    {
        /** @var self<Http\RecordEnvironment> */
        return self::httpEnvironment;
    }

    /**
     * @internal
     *
     * @return self<Http\RecordException>
     */
    public static function httpException(): self
    {
        /** @var self<Http\RecordException> */
        return self::httpException;
    }
}
