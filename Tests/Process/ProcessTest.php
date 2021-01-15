<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\FlowBundle\Tests\Process;

use PHPUnit\Framework\TestCase;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Sylius\Bundle\FlowBundle\Process\Process;
use Sylius\Bundle\FlowBundle\Process\Step\Step;
use Sylius\Bundle\FlowBundle\Validator\ProcessValidator;

/**
 * Process test.
 *
 * @author Paweł Jędrzejewski <pjedrzejewski@diweb.pl>
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class ProcessTest extends TestCase
{
    /**
     * @test
     */
    public function shouldKeepStepsInOrderWhileAddingSteps()
    {
        $process = new Process();

        $process->addStep('foo', new TestStep());
        $process->addStep('bar', new TestStep());
        $process->addStep('foobar', new TestStep());

        $correctOrder = array('foo', 'bar', 'foobar');

        foreach ($process->getOrderedSteps() as $i => $step) {
            self::assertSame($correctOrder[$i], $step->getName());
        }

        foreach ($correctOrder as $i => $name) {
            self::assertSame($name, $process->getStepByIndex($i)->getName());
        }
    }

    /**
     * @test
     */
    public function shouldKeepStepsInOrderAfterSetSteps()
    {
        $process = new Process();

        $process->setSteps(array(
            'foo' => new TestStep(),
            'bar' => new TestStep(),
            'foobar' => new TestStep()
        ));

        $correctOrder = array('foo', 'bar', 'foobar');

        foreach ($process->getOrderedSteps() as $i => $step) {
            self::assertSame($correctOrder[$i], $step->getName());
        }

        foreach ($correctOrder as $i => $name) {
            self::assertSame($name, $process->getStepByIndex($i)->getName());
        }
    }

    /**
     * @test
     */
    public function shouldAddStep()
    {
        $process = new Process();
        $step1 = new TestStep();
        $process->addStep('foo', $step1);

        $steps = $process->getSteps();
        self::assertSame($step1, $steps['foo']);
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Process::removeStep
     */
    public function shouldRemoveStep()
    {
        $process = new Process();
        $process->addStep('foo', new TestStep());
        $process->removeStep('foo');

        self::assertCount(0, $process->getSteps());
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Process::removeStep
     *
     */
    public function shouldNotRemoveStepWhenWasNotSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $process = new Process();
        $process->removeStep('foo');
    }

    /**
     * @test
     */
    public function shouldSetSteps()
    {
        $process = new Process();
        $step1 = new TestStep();
        $process->setSteps(array('foo' => $step1));

        $steps = $process->getSteps();
        self::assertSame($step1, $steps['foo']);
    }

    /**
     * @test
     *
     */
    public function shouldNotAddStepWithThisSameNameAgain()
    {
        $this->expectException(\InvalidArgumentException::class);
        $process = new Process();

        $process->addStep('foo', new TestStep());
        $process->addStep('foo', new TestStep());
    }

    /**
     * @test
     */
    public function shouldGetStepUsingIndexAfterSetSteps()
    {
        $process = new Process();

        $step1 = new TestStep();
        $step2 = new TestStep();

        $process->setSteps(array(
            'foo' => $step1,
            'bar' => $step2,
        ));

        self::assertSame($step1, $process->getStepByIndex(0));
        self::assertSame($step2, $process->getStepByIndex(1));
    }

    /**
     * @test
     */
    public function shouldGetStepUsingIndexAfterStepAddition()
    {
        $process = new Process();

        $step1 = new TestStep();
        $step2 = new TestStep();

        $process->addStep('foo', $step1);
        $process->addStep('bar', $step2);

        self::assertSame($step1, $process->getStepByIndex(0));
        self::assertSame($step2, $process->getStepByIndex(1));
    }

    /**
     * @test
     *
     */
    public function shouldNotGetStepUsingIndexWhenWasNotSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $process = new Process();
        $process->getStepByIndex(0);
    }

    /**
     * @test
     */
    public function shouldGetStepUsingNameAfterSetSteps()
    {
        $process = new Process();

        $step1 = new TestStep();
        $step2 = new TestStep();

        $process->setSteps(array(
            'foo' => $step1,
            'bar' => $step2,
        ));

        self::assertSame($step1, $process->getStepByName('foo'));
        self::assertSame($step2, $process->getStepByName('bar'));
    }

    /**
     * @test
     */
    public function shouldGetStepUsingNameAfterStepAddition()
    {
        $process = new Process();

        $step1 = new TestStep();
        $step2 = new TestStep();

        $process->addStep('foo', $step1);
        $process->addStep('bar', $step2);

        self::assertSame($step1, $process->getStepByName('foo'));
        self::assertSame($step2, $process->getStepByName('bar'));
    }

    /**
     * @test
     *
     */
    public function shouldNotGetStepUsingNameWhenWasNotSet()
    {
        $this->expectException(\InvalidArgumentException::class);
        $process = new Process();
        $process->getStepByName('foo');
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Process::getLastStep
     */
    public function shouldGetLastStep()
    {
        $process = new Process();

        $step1 = new TestStep();
        $step2 = new TestStep();

        $process->addStep('foo', $step1);
        $process->addStep('bar', $step2);

        self::assertSame($step2, $process->getLastStep());
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Process::getFirstStep
     */
    public function shouldGetFirstStep()
    {
        $process = new Process();

        $step1 = new TestStep();
        $step2 = new TestStep();

        $process->addStep('foo', $step1);
        $process->addStep('bar', $step2);

        self::assertSame($step1, $process->getFirstStep());
    }

    /**
     * @test
     */
    public function shouldSetNeededDataUsingSetter()
    {
        $process = new Process();
        $process->setScenarioAlias('alias');
        $process->setDisplayRoute('displayRoute');
        $process->setForwardRoute('forwardRoute');
        $process->setRedirect('http://somepage');
        $process->setValidator(new ProcessValidator(function () {
            return false;
        }));

        $validator = $process->getValidator();
        self::assertSame('alias', $process->getScenarioAlias());
        self::assertSame('displayRoute', $process->getDisplayRoute());
        self::assertSame('forwardRoute', $process->getForwardRoute());
        self::assertSame('http://somepage', $process->getRedirect());
        self::assertFalse($validator->isValid());
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Process::countSteps
     * @dataProvider countStepsDataProvider
     */
    public function shouldCountSteps($steps, $expectedCount)
    {
        $process = new Process();

        $process->setSteps($steps);

        self::assertEquals($process->countSteps(), $expectedCount);
    }

    public function countStepsDataProvider()
    {
        return array(
            array(
                array(new TestStep(), new TestStep()),
                2
            ),
            array(
                array('abc' => new TestStep(), 'abc' => new TestStep()),
                1
            ),
            array(
                array('abc' => new TestStep()),
                1
            ),
            array(
                array('abc' => new TestStep(), 'zzz' => new TestStep(), 'yyy' => new TestStep()),
                3
            ),
        );
    }
}

class TestStep extends Step
{
    public function displayAction(ProcessContextInterface $context)
    {
        // pufff.
    }
}
