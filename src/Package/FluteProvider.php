<?php declare (strict_types = 1);

namespace Whoa\Flute\Package;

use Whoa\Contracts\Provider\ProvidesContainerConfiguratorsInterface;

/**
 * @package Whoa\Flute
 */
class FluteProvider implements ProvidesContainerConfiguratorsInterface
{
    /**
     * @inheritdoc
     */
    public static function getContainerConfigurators(): array
    {
        return [
            FluteContainerConfigurator::class,
        ];
    }
}
