<?php

/**
 * Copyright 2015-2019 info@neomerx.com
 * Modification Copyright 2021-2022 info@whoaphp.com
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare (strict_types=1);

namespace Whoa\Flute\Http\ThrowableHandlers;

use Exception;
use Whoa\Common\Reflection\ClassIsTrait;
use Whoa\Contracts\Exceptions\ThrowableHandlerInterface;
use Whoa\Contracts\Http\ThrowableResponseInterface;
use Whoa\Flute\Contracts\Encoder\EncoderInterface;
use Whoa\Flute\Contracts\Exceptions\JsonApiThrowableConverterInterface as ConverterInterface;
use Whoa\Flute\Http\JsonApiResponse;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\JsonApi\Schema\Error;
use Neomerx\JsonApi\Schema\ErrorCollection;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareTrait;
use Throwable;

use function array_key_exists;
use function assert;
use function get_class;

/**
 * @package Whoa\Flute
 */
class FluteThrowableHandler implements ThrowableHandlerInterface
{
    use ClassIsTrait;
    use LoggerAwareTrait;

    /**
     * Those classes will not be logged. Note that classes are expected to be keys but not values.
     *
     * @var array
     */
    private array $doNotLogClassesAsKeys;

    /**
     * @var int
     */
    private int $httpCodeForUnexpected;

    /**
     * @var bool
     */
    private bool $isDebug;

    /**
     * @var EncoderInterface
     */
    private EncoderInterface $encoder;

    /**
     * @var string|null
     */
    private ?string $throwableConverter;

    /**
     * @param EncoderInterface $encoder
     * @param array $noLogClassesAsKeys
     * @param int $codeForUnexpected
     * @param bool $isDebug
     * @param null|string $converterClass
     */
    public function __construct(
        EncoderInterface $encoder,
        array $noLogClassesAsKeys,
        int $codeForUnexpected,
        bool $isDebug,
        ?string $converterClass
    ) {
        assert(
            $converterClass === null ||
            static::classImplements($converterClass, ConverterInterface::class)
        );

        $this->doNotLogClassesAsKeys = $noLogClassesAsKeys;
        $this->httpCodeForUnexpected = $codeForUnexpected;
        $this->isDebug = $isDebug;
        $this->encoder = $encoder;
        $this->throwableConverter = $converterClass;
    }

    /**
     * @inheritdoc
     */
    public function createResponse(Throwable $throwable, ContainerInterface $container): ThrowableResponseInterface
    {
        unset($container);

        $message = 'Internal Server Error';
        $isJsonApiException = $throwable instanceof JsonApiException;

        $this->logError($throwable, $message);

        // if exception converter is specified it will be used to convert throwable to JsonApiException
        if ($isJsonApiException === false && $this->throwableConverter !== null) {
            try {
                /** @var ConverterInterface $converterClass */
                $converterClass = $this->throwableConverter;
                if (($converted = $converterClass::convert($throwable)) !== null) {
                    assert($converted instanceof JsonApiException);
                    $throwable = $converted;
                    $isJsonApiException = true;
                }
            } catch (Throwable $ignored) {
            }
        }

        // compose JSON API Error with appropriate level of details
        if ($isJsonApiException === true) {
            /** @var JsonApiException $throwable */
            $errors = $throwable->getErrors();
            $httpCode = $throwable->getHttpCode();
        } else {
            $errors = new ErrorCollection();
            $httpCode = $this->getHttpCodeForUnexpectedThrowable();
            $details = null;
            if ($this->isDebug === true) {
                $message = $throwable->getMessage();
                $details = (string)$throwable;
            }
            $errors->add(new Error(null, null, null, (string)$httpCode, null, $message, $details));
        }

        // encode the error and send to client
        $content = $this->encoder->encodeErrors($errors);

        return $this->createThrowableJsonApiResponse($throwable, $content, $httpCode);
    }

    /**
     * @param Throwable $throwable
     * @param string $message
     * @return void
     */
    private function logError(Throwable $throwable, string $message): void
    {
        if ($this->logger !== null && $this->shouldBeLogged($throwable) === true) {
            // on error (e.g. no permission to write on disk or etc) ignore
            try {
                $this->logger->error($message, ['error' => $throwable]);
            } catch (Exception $exception) {
            }
        }
    }

    /**
     * @return int
     */
    private function getHttpCodeForUnexpectedThrowable(): int
    {
        return $this->httpCodeForUnexpected;
    }

    /**
     * @param Throwable $throwable
     * @return bool
     */
    private function shouldBeLogged(Throwable $throwable): bool
    {
        return array_key_exists(get_class($throwable), $this->doNotLogClassesAsKeys) === false;
    }

    /**
     * @param Throwable $throwable
     * @param string $content
     * @param int $status
     * @return ThrowableResponseInterface
     */
    private function createThrowableJsonApiResponse(
        Throwable $throwable,
        string $content,
        int $status
    ): ThrowableResponseInterface {
        return new class ($throwable, $content, $status) extends JsonApiResponse implements ThrowableResponseInterface {
            /**
             * @var Throwable
             */
            private Throwable $throwable;

            /**
             * @param Throwable $throwable
             * @param string $content
             * @param int $status
             */
            public function __construct(Throwable $throwable, string $content, int $status)
            {
                parent::__construct($content, $status);
                $this->throwable = $throwable;
            }

            /**
             * @return Throwable
             */
            public function getThrowable(): Throwable
            {
                return $this->throwable;
            }
        };
    }
}
