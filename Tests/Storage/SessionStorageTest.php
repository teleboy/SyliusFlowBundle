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
use Sylius\Bundle\FlowBundle\Storage\SessionStorage;

/**
 * SessionStorage test.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class SessionStorageTest extends TestCase
{
    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Storage\SessionStorage
     */
    public function shouldSetValueToSessionBag()
    {
        $sessionBag = $this->getSessionBag();
        $sessionBag->expects(self::once())
            ->method('set')
            ->with('mydomain/test', 'my-value');
        $sessionBag->expects(self::once())
            ->method('get')
            ->with('mydomain/test')
            ->willReturn('my-value');

        $sessionStorage = new SessionStorage($this->getSession($sessionBag));
        $sessionStorage->initialize('mydomain');
        $sessionStorage->set('test', 'my-value');

        self::assertEquals('my-value', $sessionStorage->get('test'));
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Storage\SessionStorage
     */
    public function shouldCheckIfValueIsSetInSessionBag()
    {
        $sessionBag = $this->getSessionBag();
        $sessionBag->expects(self::once())
            ->method('has')
            ->with('mydomain/test')
            ->willReturn(true);

        $sessionStorage = new SessionStorage($this->getSession($sessionBag));
        $sessionStorage->initialize('mydomain');

        self::assertTrue($sessionStorage->has('test'));
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Storage\SessionStorage
     */
    public function shouldRemoveFromSessionBag()
    {
        $sessionBag = $this->getSessionBag();
        $sessionBag->expects(self::once())
            ->method('remove')
            ->with('mydomain/test');

        $sessionStorage = new SessionStorage($this->getSession($sessionBag));
        $sessionStorage->initialize('mydomain');

        $sessionStorage->remove('test');
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Storage\SessionStorage
     */
    public function shouldClearDomainInSessionBag()
    {
        $sessionBag = $this->getSessionBag();
        $sessionBag->expects(self::once())
            ->method('remove')
            ->with('mydomain');

        $sessionStorage = new SessionStorage($this->getSession($sessionBag));
        $sessionStorage->initialize('mydomain');

        $sessionStorage->clear();
    }

    private function getSessionBag()
    {
        return $this->getMockBuilder('Sylius\Bundle\FlowBundle\Storage\SessionFlowsBag')->getMock();
    }

    private function getSession($bag)
    {
        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\SessionInterface')->getMock();
        $session
            ->method('getBag')
            ->willReturn($bag);

        return $session;
    }
}
