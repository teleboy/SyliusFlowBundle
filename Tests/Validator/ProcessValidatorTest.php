<?php

namespace Sylius\Bundle\FlowBundle\Tests\Validator;

use PHPUnit\Framework\TestCase;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Sylius\Bundle\FlowBundle\Process\Process;
use Sylius\Bundle\FlowBundle\Process\Step\ControllerStep;
use Sylius\Bundle\FlowBundle\Validator\ProcessValidator;
use Symfony\Bundle\FrameworkBundle\Templating\Loader\FilesystemLoader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProcessValidatorTest extends TestCase
{
    /**
     * @test
     */
    public function shouldBeValid()
    {
        $validator = new ProcessValidator(function() {
            return true;
        });

        self::assertTrue($validator->isValid());
    }

    /**
     * @test
     */
    public function shouldBeInvalid()
    {
        $validator = new ProcessValidator(function() {
            return false;
        });

        self::assertNotTrue($validator->isValid());
    }

    /**
     * @test
     *
     */
    public function shouldThrowException()
    {
        $this->expectException(HttpException::class);
        $process = new Process();

        $process->addStep('foo', new TestStep());

        $process->setValidator(new ProcessValidator(function() {
            return false;
        }));

        if (!$process->getValidator()->isValid()) {
            $process->getValidator()->getResponse($process->getStepByName('foo'));
        }
    }
}

class TestStep extends ControllerStep
{
    public function displayAction(ProcessContextInterface $context)
    {
        // pufff.
    }
}

