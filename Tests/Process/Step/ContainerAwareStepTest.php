<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\FlowBundle\Tests\Process\Step;

use PHPUnit\Framework\TestCase;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Sylius\Bundle\FlowBundle\Process\Step\ContainerAwareStep;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ContainerAwareStepTest test.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class ContainerAwareStepTest extends TestCase
{
    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Step\ContainerAwareStep::setContainer
     */
    public function shouldInjectContainerBySetter()
    {
        $container = $this->getMockBuilder(ContainerInterface::class)->getMock();
        $step = new TestContainerAwareStep();
        $step->setContainer($container);

        self::assertSame($step->getContainer(), $container);
    }
}

class TestContainerAwareStep extends ContainerAwareStep
{
    /**
     * Just for check if container setter works
     */
    public function getContainer()
    {
        return $this->container;
    }

    public function displayAction(ProcessContextInterface $context)
    {
        // pufff.
    }
}
