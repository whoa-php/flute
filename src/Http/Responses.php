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

namespace Whoa\Flute\Http;

use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\BaseResponses;

/**
 * @package Whoa\Flute
 */
class Responses extends BaseResponses
{
    /**
     * @var EncoderInterface
     */
    private EncoderInterface $encoder;

    /**
     * @var MediaTypeInterface
     */
    private MediaTypeInterface $outputMediaType;

    /**
     * @param MediaTypeInterface $outputMediaType
     * @param EncoderInterface $encoder
     */
    public function __construct(
        MediaTypeInterface $outputMediaType,
        EncoderInterface $encoder
    ) {
        $this->encoder = $encoder;
        $this->outputMediaType = $outputMediaType;
    }

    /**
     * @inheritdoc
     */
    protected function createResponse(?string $content, int $statusCode, array $headers)
    {
        return new JsonApiResponse($content, $statusCode, $headers);
    }

    /**
     * @inheritdoc
     */
    protected function getEncoder(): EncoderInterface
    {
        return $this->encoder;
    }

    /**
     * @inheritdoc
     */
    protected function getMediaType(): MediaTypeInterface
    {
        return $this->outputMediaType;
    }
}
