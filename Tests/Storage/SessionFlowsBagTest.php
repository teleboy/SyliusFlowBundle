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
use Sylius\Bundle\FlowBundle\Storage\SessionFlowsBag;

/**
 * SessionFlowsBag test.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class SessionFlowsBagTest extends TestCase
{
    /**
     * @test
     */
    public function shouldGetName()
    {
        $sessionBag = new SessionFlowsBag();

        self::assertEquals('sylius.flow.bag', $sessionBag->getName());
    }

    /**
     * @test
     */
    public function shouldSetValue()
    {
        $sessionBag = new SessionFlowsBag();
        $sessionBag->set('key', 'value');

        self::assertEquals('value', $sessionBag->get('key'));
    }
}
