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
use Sylius\Bundle\FlowBundle\Process\Step\ActionResult;

/**
 * Step test.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class StepTest extends TestCase
{
    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Step\Step::isActive
     */
    public function shouldByActiveByDefault()
    {
        $step = $this->getStep();

        self::assertTrue($step->isActive());
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Step\Step::forwardAction
     */
    public function shouldCompleteProcessByDefault()
    {
        $processContext = $this->getMockBuilder('Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface')->getMock();

        $step = $this->getStep();
        /** @var $result ActionResult */
        $result = $step->forwardAction($processContext);
        self::assertEmpty($result->getNextStepName());
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Step\Step::setName
     * @covers Sylius\Bundle\FlowBundle\Process\Step\Step::getName
     */
    public function shouldSetName()
    {
        $step = $this->getStep();
        $step->setName('stepName');

        self::assertSame('stepName', $step->getName());
    }

    private function getStep()
    {
        return $this->getMockForAbstractClass('Sylius\Bundle\FlowBundle\Process\Step\Step');
    }
}
