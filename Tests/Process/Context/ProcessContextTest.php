<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\FlowBundle\Tests\Process\Context;

use PHPUnit\Framework\TestCase;
use Sylius\Bundle\FlowBundle\Storage\StorageInterface;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContext;
use Sylius\Bundle\FlowBundle\Validator\ProcessValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * ProcessContext test.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class ProcessContextTest extends TestCase
{
    /**
     * @test
     * @dataProvider getMethodsWithoutInitialize
     *
     */
    public function shouldNotExecuteMethodsWithoutContextInitialize($methodName): void
    {
        $this->expectException(\RuntimeException::class);
        $context = new ProcessContext($this->getMockBuilder(StorageInterface::class)->getMock());
        $context->$methodName();
    }

    public function getMethodsWithoutInitialize(): array
    {
        return array(
            array('isValid'),
            array('getProcess'),
            array('getCurrentStep'),
            array('getPreviousStep'),
            array('getNextStep'),
            array('isFirstStep'),
            array('isLastStep'),
            array('close'),
            array('getProgress'),
            array('getProgress'),
        );
    }

    /**
     * @test
     */
    public function shouldInitializeStorage(): void
    {
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $storage->expects(self::once())
            ->method('initialize')
            ->with($this->equalTo(md5('scenarioOne')));

        $context = new ProcessContext($storage);
        $context->initialize($this->getProcess(), $this->getStep('myStep'));
    }

    /**
     * @test
     */
    public function shouldSetPreviousStepWhenInitialize()
    {
        $steps = array(
            $this->getStep('step1'),
            $this->getStep('step2')
        );
        $process = $this->getProcess($steps);
        $context = new ProcessContext($this->getMockBuilder(StorageInterface::class)->getMock());
        $context->initialize($process, $steps[1]);

        self::assertEquals('step1', $context->getPreviousStep()->getName());
    }

    /**
     * @test
     */
    public function shouldSetNextStepWhenInitialize()
    {
        $steps = array(
            $this->getStep('step1'),
            $this->getStep('step2')
        );
        $process = $this->getProcess($steps);
        $context = new ProcessContext($this->getMockBuilder(StorageInterface::class)->getMock());
        $context->initialize($process, $steps[0]);

        self::assertEquals('step2', $context->getNextStep()->getName());
    }

    /**
     * @test
     */
    public function shouldSetCurrentStepWhenInitialize(): void
    {
        $steps = array(
            $this->getStep('step1'),
            $this->getStep('step2')
        );
        $process = $this->getProcess($steps);
        $context = new ProcessContext($this->getMockBuilder(StorageInterface::class)->getMock());
        $context->initialize($process, $steps[0]);

        self::assertSame($steps[0], $context->getCurrentStep());
    }

    /**
     * @test
     */
    public function shouldKnowWhenFirstStep()
    {
        $steps = array(
            $this->getStep('step1'),
            $this->getStep('step2')
        );
        $process = $this->getProcess($steps);

        $firstStepContext = new ProcessContext($this->getMockBuilder(StorageInterface::class)->getMock());
        $firstStepContext->initialize($process, $steps[0]);
        $lastStepContext = new ProcessContext($this->getMockBuilder(StorageInterface::class)->getMock());
        $lastStepContext->initialize($process, $steps[1]);

        self::assertTrue($firstStepContext->isFirstStep());
        self::assertFalse($lastStepContext->isFirstStep());
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function shouldClearStorageWhenClose(): void
    {
        $storage = $this->getMockBuilder(StorageInterface::class)->getMock();
        $storage->expects(self::once())
            ->method('clear');

        $context = new ProcessContext($storage);
        $context->initialize($this->getProcess(), $this->getStep('myStep'));
        $context->close();
    }

    /**
     * @test
     */
    public function shouldKnowWhenLastStep(): void
    {
        $steps = array(
            $this->getStep('step1'),
            $this->getStep('step2')
        );
        $process = $this->getProcess($steps);

        $firstStepContext = new ProcessContext($this->getMockBuilder(StorageInterface::class)->getMock());
        $firstStepContext->initialize($process, $steps[0]);
        $lastStepContext = new ProcessContext($this->getMockBuilder(StorageInterface::class)->getMock());
        $lastStepContext->initialize($process, $steps[1]);

        self::assertFalse($firstStepContext->isLastStep());
        self::assertTrue($lastStepContext->isLastStep());
    }

    /**
     * @test
     */
    public function shouldSetRequest(): void
    {
        $request = $this->getMockBuilder(Request::class)->getMock();

        $context = new ProcessContext($this->getMockBuilder(StorageInterface::class)->getMock());
        $context->setRequest($request);

        self::assertSame($request, $context->getRequest());
    }

    /**
     * @test
     */
    public function shouldGetProcess()
    {
        $steps = array(
            $this->getStep('step1'),
            $this->getStep('step2')
        );
        $process = $this->getProcess($steps);

        $context = new ProcessContext($this->getMockBuilder(StorageInterface::class)->getMock());
        $context->initialize($process, $steps[0]);

        self::assertSame($process, $context->getProcess());
    }

    /**
     * @test
     */
    public function shouldNotBeValidWhenNotInitialized(): void
    {
        $this->expectException(\RuntimeException::class);
        $context = new ProcessContext($this->getMockBuilder(StorageInterface::class)->getMock());

        $context->isValid();
    }

    /**
     * @test
     */
    public function shouldNotBeValidWhenProcessValidatorIsNotValid(): void
    {
        $steps = array(
            $this->getStep('step1'),
            $this->getStep('step2')
        );
        $process = $this->getProcess($steps);
        $process->expects(self::once())
            ->method('getValidator')
            ->willReturn(
                    new ProcessValidator(
                            function () {
                                return false;
                            }
                    )
            );

        $context = new ProcessContext($this->getMockBuilder('Sylius\Bundle\FlowBundle\Storage\StorageInterface')->getMock());
        $context->initialize($process, $steps[0]);

        self::assertNotTrue($context->isValid());
    }

    /**
     * @test
     */
    public function shouldNotBeValidWhenStepIsNotInHistory(): void
    {
        $steps = array(
            $this->getStep('step1'),
            $this->getStep('step2')
        );
        $process = $this->getProcess($steps);

        $storage = new TestArrayStorage();
        $history = array('step1');
        $storage->set('history', $history);

        $context = new ProcessContext($storage);
        $context->initialize($process, $steps[1]);

        self::assertFalse($context->isValid());
    }

    /**
     * @test
     * @runInSeparateProcess
     */
    public function shouldRewindHistory(): void
    {
        $steps = array(
            $this->getStep('step1'),
            $this->getStep('step2'),
        );
        $process = $this->getProcess($steps);

        $storage = new TestArrayStorage();
        $history = array('step1', 'step2');
        $storage->set('history', $history);

        $context = new ProcessContext($storage);
        $context->initialize($process, $steps[0]);

        self::assertTrue($context->isValid());
        $context->rewindHistory();
        self::assertCount(1, $storage->get('history'));
        self::assertContains('step1', $storage->get('history'));
        self::assertNotContains('step2', $storage->get('history'));
    }

    /**
     * @test
     *
     */
    public function shouldFailToRewindHistory()
    {
        $steps = array(
            $this->getStep('step1'),
            $this->getStep('step2'),
        );
        $process = $this->getProcess($steps);

        $storage = new TestArrayStorage();
        $history = array('stepX', 'stepY');
        $storage->set('history', $history);

        $context = new ProcessContext($storage);
        $context->initialize($process, $steps[0]);

        $this->expectException(NotFoundHttpException::class);
        $context->rewindHistory();
    }

    /**
     * @test
     */
    public function shouldBeValidWithEmptyHistory()
    {
        $steps = array(
            $this->getStep('step1'),
            $this->getStep('step2')
        );
        $process = $this->getProcess($steps);

        $context = new ProcessContext(new TestArrayStorage());
        $context->initialize($process, $steps[0]);

        self::assertTrue($context->isValid());
    }

    /**
     * @test
     */
    public function shouldBeValidWithHistory()
    {
        $steps = array(
            $this->getStep('step1'),
            $this->getStep('step2')
        );
        $process = $this->getProcess($steps);

        $storage = new TestArrayStorage();
        $history = array('step1', 'step2');
        $storage->set('history', $history);
        $context = new ProcessContext($storage);
        $context->initialize($process, $steps[0]);

        self::assertTrue($context->isValid());
    }

    /**
     * @test
     */
    public function shouldBeValidWithoutHistory(): void
    {
        $process = $this->getProcess(array());

        $context = new ProcessContext($this->getMockBuilder(StorageInterface::class)->getMock());
        $context->initialize($process, $this->getStep('someStep'));

        self::assertTrue($context->isValid());
    }

    /**
     * @test
     * @dataProvider getProgressData
     */
    public function shouldCalculateProgress($steps, $index, $expectedProgress)
    {
        $process = $this->getProcess($steps);

        $context = new ProcessContext($this->getMockBuilder('Sylius\Bundle\FlowBundle\Storage\StorageInterface')->getMock());
        $context->initialize($process, $steps[$index]);

        self::assertEquals($context->getProgress(), $expectedProgress);
    }

    public function getProgressData()
    {
        return array(
            array(
                array(
                    $this->getStep('step1'),
                    $this->getStep('step2')
                ),
                0,
                50
            ),
            array(
                array(
                    $this->getStep('step1'),
                    $this->getStep('step2')
                ),
                1,
                100
            ),
            array(
                array(
                    $this->getStep('step1'),
                    $this->getStep('step2'),
                    $this->getStep('step3')
                ),
                0,
                33
            ),
            array(
                array(
                    $this->getStep('step1'),
                    $this->getStep('step2'),
                    $this->getStep('step3')
                ),
                1,
                66
            ),
        );
    }

    /**
     * @test
     */
    public function shouldInjectStorageBySetter()
    {
        $storage1 = $this->getMockBuilder('Sylius\Bundle\FlowBundle\Storage\StorageInterface')->getMock();
        $storage2 = $this->getMockBuilder('Sylius\Bundle\FlowBundle\Storage\StorageInterface')->getMock();

        $context = new ProcessContext($storage1);
        $context->setStorage($storage2);

        self::assertSame($storage2, $context->getStorage());
    }

    private function getProcess($steps = array())
    {
        $process = $this->getMockBuilder('Sylius\Bundle\FlowBundle\Process\ProcessInterface')->getMock();
        $process->expects($this->any())
            ->method('setScenarioAlias')
            ->with($this->equalTo('scenarioOne'));
        $process->expects($this->any())
            ->method('getScenarioAlias')
            ->will($this->returnValue('scenarioOne'));
        $process->expects($this->any())
            ->method('getOrderedSteps')
            ->will($this->returnValue($steps));
        $process->expects($this->any())
            ->method('countSteps')
            ->will($this->returnValue(count($steps)));

        return $process;
    }

    private function getStep($name)
    {
        $step = $this->getMockBuilder('Sylius\Bundle\FlowBundle\Process\Step\StepInterface')->getMock();
        $step->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $step->expects($this->any())
            ->method('displayAction')
            ->will($this->returnValue('displayActionResponse'));
        $step->expects($this->any())
            ->method('forwardAction')
            ->will($this->returnValue('forwardActionResponse'));

        return $step;
    }
}

class TestArrayStorage implements StorageInterface
{
    private $data = array();

    public function initialize($domain)
    {

    }

    public function has($key)
    {
        return isset($this->data[$key]);
    }

    public function get($key, $default = null)
    {
        return $this->has($key) ? $this->data[$key] : $default;
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function remove($key)
    {
        unset($this->data[$key]);
    }

    public function clear()
    {
        $this->data = array();
    }
}
