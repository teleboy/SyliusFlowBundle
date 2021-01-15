<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\FlowBundle\Tests\Process\Builder;

use PHPUnit\Framework\TestCase;
use Sylius\Bundle\FlowBundle\Process\Builder\ProcessBuilder;
use Sylius\Bundle\FlowBundle\Process\Process;
use Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Sylius\Bundle\FlowBundle\Process\ProcessInterface;
use Sylius\Bundle\FlowBundle\Process\Step\StepInterface;
use Sylius\Bundle\FlowBundle\Process\Step\ContainerAwareStep;

/**
 * ProcessBuilder test.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class ProcessBuilderTest extends TestCase
{
    private $builder;

    public function setUp(): void
    {
        $this->builder = new TestProcessBuilder($this->getMockBuilder(ContainerInterface::class)->getMock());
    }

    /**
     * @test
     */
    public function shouldCreateProcess(): void
    {
        $process = $this->builder->build($this->getMockBuilder(ProcessScenarioInterface::class)->getMock());

        self::assertInstanceOf(Process::class, $process);
    }

    /**
     * @test
     */
    public function shouldBuildScenario()
    {
        $scenario = $this->getMockBuilder('Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface')->getMock();
        $scenario->expects($this->once())
            ->method('build')
            ->with($this->equalTo($this->builder));

        $this->builder->build($scenario);
    }

    /**
     * @test
     *
     */
    public function shouldNotAddWihtoutProcess(): void
    {
        $this->expectException(\RuntimeException::class);
        $process = $this->getMockBuilder(ProcessInterface::class)->getMock();

        $this->builder->registerStep('new', $this->getStep('somename'));
        $this->builder->add('somename', 'new');
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Builder\ProcessBuilder::add
     */
    public function shouldInjectContainerToContainerAwareStep()
    {
        $step = $this->getMockBuilder(ContainerAwareStep::class)->getMock();
        $step->expects(self::once())
            ->method('setContainer')
            ->with(self::isInstanceOf(ContainerInterface::class));

        $this->builder->build($this->getMockBuilder(ProcessScenarioInterface::class)->getMock());
        $this->builder->add('somename', $step);
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Builder\ProcessBuilder::add
     * @covers Sylius\Bundle\FlowBundle\Process\Builder\ProcessBuilder::registerStep
     */
    public function shouldAcceptStepAliasWhileAdding()
    {
        $step = $this->getStep();
        $step->expects($this->any())
            ->method('setName')
            ->with($this->equalTo('somename'));

        $this->builder->build($this->getMockBuilder('Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface')->getMock());
        $this->builder->registerStep('new', $step);
        $this->builder->add('somename', 'new');

        self::assertSame($step, $this->builder->getProcess()->getStepByName('somename'));
        self::assertCount(1, $this->builder->getProcess()->getSteps());
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Builder\ProcessBuilder::add
     *
     */
    public function shouldNotAddObjectWhichAreNotSteps()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder->build($this->getMockBuilder(ProcessScenarioInterface::class)->getMock());
        $this->builder->add('some', new \stdClass);
    }

    /**
     * @test
     */
    public function shouldNotRemoveStepWithoutProcess()
    {
        $this->expectException(\RuntimeException::class);
        $this->builder->remove('test');
    }

    /**
     * @test
     */
    public function shouldRemoveStepFromProcess()
    {
        $this->builder->build($this->getMockBuilder(ProcessScenarioInterface::class)->getMock());
        $this->builder->add('some', $this->getStep('some'));
        $this->builder->remove('some');

        self::assertCount(0, $this->builder->getProcess()->getSteps());
    }

    /**
     * @test
     */
    public function shouldNotCheckIfStepIsSetWithoutProcess(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->builder->has('test');
    }

    /**
     * @test
     */
    public function shouldCheckIfStepIsSet(): void
    {
        $this->builder->build($this->getMockBuilder(ProcessScenarioInterface::class)->getMock());

        self::assertFalse($this->builder->has('some'));
        $this->builder->add('some', $this->getStep('some'));
        self::assertTrue($this->builder->has('some'));
    }

    /**
     * @test
     */
    public function shouldNotInjectDisplayRouteWithoutProcess(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->builder->setDisplayRoute('display_route');
    }

    /**
     * @test
     */
    public function shouldInjectDisplayRouteToProcess(): void
    {
        $this->builder->build($this->getMockBuilder(ProcessScenarioInterface::class)->getMock());
        $this->builder->setDisplayRoute('display_route');

        self::assertEquals('display_route', $this->builder->getProcess()->getDisplayRoute());
    }

    /**
     * @test
     */
    public function shouldNotInjectForwardRouteWithoutProcess(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->builder->setForwardRoute('forward_route');
    }

    /**
     * @test
     */
    public function shouldInjectForwardRouteToProcess(): void
    {
        $this->builder->build($this->getMockBuilder(ProcessScenarioInterface::class)->getMock());
        $this->builder->setForwardRoute('forward_route');

        self::assertEquals('forward_route', $this->builder->getProcess()->getForwardRoute());
    }

    /**
     * @test
     *
     */
    public function shouldNotInjectRedirectWithoutProcess(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->builder->setRedirect('redirect');
    }

    /**
     * @test
     */
    public function shouldInjectRedirectToProcess(): void
    {
        $this->builder->build($this->getMockBuilder(ProcessScenarioInterface::class)->getMock());
        $this->builder->setRedirect('redirect');

        self::assertEquals('redirect', $this->builder->getProcess()->getRedirect());
    }

    /**
     * @test
     *
     */
    public function shouldNotInjectValidationClosureWithoutProcess(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->builder->validate(function () {
            return 'my-closure';
        });
    }

    /**
     * @test
     */
    public function shouldInjectValidationClosureToProcess()
    {
        $this->builder->build($this->getMockBuilder(ProcessScenarioInterface::class)->getMock());
        $this->builder->validate(function () {
            return false;
        });

        $validator = $this->builder->getProcess()->getValidator();
        self::assertEquals(false, $validator->isValid());
    }

    /**
     * @test
     *
     */
    public function shouldNotRegisterTwoThisSameSteps()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder->registerStep('new', $this->getStep('somename'));
        $this->builder->registerStep('new', $this->getStep('somename'));
    }

    /**
     * @test
     */
    public function shouldNotLoadStepWhenWasNotRegisteredBefore(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->builder->loadStep('new');
    }

    /**
     * @test
     */
    public function shouldLoadStep(): void
    {
        $step = $this->getStep('somename');
        $this->builder->registerStep('new', $step);

        self::assertSame($this->builder->loadStep('new'), $step);
    }

    private function getStep($name = '')
    {
        $step = $this->getMockBuilder(StepInterface::class)->getMock();
        $step
            ->method('getName')
            ->willReturn($name);
        $step
            ->method('displayAction')
            ->willReturn('displayActionResponse');
        $step
            ->method('forwardAction')
            ->willReturn('forwardActionResponse');

        return $step;
    }
}

class TestProcessBuilder extends ProcessBuilder
{
    /**
     * Method getProcess exists only in TestProcessBuilder to allow testing
     */
    public function getProcess()
    {
        return $this->process;
    }
}
