<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\FlowBundle\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Sylius\Bundle\FlowBundle\Storage\Storage;

/**
 * Storage test.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class StorageTest extends TestCase
{
    /**
     * @test
     */
    public function shouldSetDomainWhenInitialize(): void
    {
        $storage = $this->getMockForAbstractClass(Storage::class);
        $storage->initialize('mydomain');

        self::assertObjectHasAttribute('domain', $storage);
    }
}
