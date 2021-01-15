<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\FlowBundle\Tests\Process\Coordinator;

use PHPUnit\Framework\TestCase;
use Sylius\Bundle\FlowBundle\Validator\ProcessValidator;
use Symfony\Component\HttpFoundation\Response;

use Sylius\Bundle\FlowBundle\Process\Coordinator\Coordinator;
use Sylius\Bundle\FlowBundle\Process\Coordinator\CoordinatorInterface;
use Sylius\Bundle\FlowBundle\Process\Step\ActionResult;
use Sylius\Bundle\FlowBundle\Process\Context\ProcessContextInterface;
use Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Coordinator test.
 *
 * @author Leszek Prabucki <leszek.prabucki@gmail.com>
 */
class CoordinatorTest extends TestCase
{
    /** @var CoordinatorInterface */
    private $coordinator;

    public function setUp(): void
    {
        $router = $this->getRouter(
            'sylius_flow_display',
             array(
                'scenarioAlias' => 'scenarioOne',
                'stepName' => 'firstStepName'
            ),
            'http://someurl.dev/step/scenarioOne/firstStepName'
        );

        $processBuilder = $this->getProcessBuilder($this->getProcess());

        $processContext = $this->getProcessContext();
        $processContext->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue(true));
        $processContext->expects($this->any())
            ->method('getStepHistory')
            ->will($this->returnValue(array()));
        $processContext->expects($this->any())
            ->method('rewindHistory')
            ->will($this->returnValue(null));

        $this->coordinator = $this->createCoordinator($router, $processBuilder, $processContext);
    }

    /**
     * @test
     */
    public function shouldRedirectToDefaultDisplayActionWhenStarting()
    {
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder('Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface')->getMock());

        $response = $this->coordinator->start('scenarioOne');

        self::assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        self::assertEquals('http://someurl.dev/step/scenarioOne/firstStepName', $response->getTargetUrl());
    }

    /**
     * @test
     */
    public function shouldRedirectToMyRouteDisplayActionWhenStarting()
    {
        $router = $this->getRouter(
            'my_route',
             array(
                'stepName' => 'firstStepName'
            ),
            'http://someurl.dev/my-super-route/firstStepName'
        );

        $process = $this->getProcess();
        $process->expects($this->any())
            ->method('getDisplayRoute')
            ->will($this->returnValue('my_route'));

        $processBuilder = $this->getProcessBuilder($process);

        $processContext = $this->getProcessContext();
        $processContext->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue(true));
        $processContext->expects($this->any())
            ->method('getStepHistory')
            ->will($this->returnValue(array()));

        $this->coordinator = $this->createCoordinator($router, $processBuilder, $processContext);
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder(ProcessScenarioInterface::class)->getMock());
        $response = $this->coordinator->start('scenarioOne');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals('http://someurl.dev/my-super-route/firstStepName', $response->getTargetUrl());
    }

    /**
     * @test
     *
     *
     */
    public function shouldNotStartWhenScenarioIsNotRegistered(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Process scenario with alias \"scenarioOne\" is not registered");
        $this->coordinator->start('scenarioOne');
    }

    /**
     * @test
     *
     */
    public function shouldNotStartWhenProcessIsNotValid(): void
    {
        $this->expectException(HttpException::class);
        $router = $this->getRouter();

        $processBuilder = $this->getProcessBuilder($this->getProcess());

        $processContext = $this->getProcessContext();
        $processContext
            ->method('isValid')
            ->willReturn(
                    new ProcessValidator(
                            function () {
                                return false;
                            }
                    )
            );

        $this->coordinator = $this->createCoordinator($router, $processBuilder, $processContext);
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder(ProcessScenarioInterface::class)->getMock());
        $this->coordinator->start('scenarioOne');
    }

    /**
     * @test
     */
    public function shouldShowDisplayAction()
    {
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder(ProcessScenarioInterface::class)->getMock());

        $result = $this->coordinator->display('scenarioOne', 'someStepName');

        self::assertInstanceOf(Response::class, $result);
    }

    /**
     * @test
     */
    public function shouldNotShowDisplayActionWhenProcessIsNotValid()
    {
        $this->expectException(HttpException::class);
        $router = $this->getRouter();

        $processBuilder = $this->getProcessBuilder($this->getProcess());

        $processContext = $this->getProcessContext();
        $processContext
            ->method('isValid')
            ->willReturn(
                    new ProcessValidator(
                            function () {
                                return false;
                            }
                    )
            );

        $this->coordinator = $this->createCoordinator($router, $processBuilder, $processContext);
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder(ProcessScenarioInterface::class)->getMock());
        $this->coordinator->display('scenarioOne', 'someStepName');
    }

    /**
     * @test
     */
    public function shouldNotShowDisplayActionWhenScenarioIsNotRegistered()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Process scenario with alias \"scenarioOne\" is not registered");
        $this->coordinator->display('scenarioOne', 'someStepName');
    }

    /**
     * @test
     */
    public function shouldNotShowForwardActionWhenScenarioIsNotRegistered()
    {
        $this->expectExceptionMessage("Process scenario with alias \"scenarioOne\" is not registered");
        $this->expectException(\InvalidArgumentException::class);
        $this->coordinator->forward('scenarioOne', 'someStepName');
    }

    /**
     * @test
     *
     */
    public function shouldNotShowForwardWhenProcessIsNotValid()
    {
        $this->expectException(HttpException::class);
        $router = $this->getRouter();

        $processBuilder = $this->getProcessBuilder($this->getProcess());

        $processContext = $this->getProcessContext();
        $processContext->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue(new ProcessValidator(function() { return false; })));

        $this->coordinator = $this->createCoordinator($router, $processBuilder, $processContext);
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder('Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface')->getMock());
        $this->coordinator->forward('scenarioOne', 'someStepName');
    }

    /**
     * @test
     *
     */
    public function shouldNotShowFormWhenForwardReturnsUnexpectedType()
    {
        $this->expectException(\RuntimeException::class);
        $router = $this->getRouter();

        $processBuilder = $this->getProcessBuilder($this->getProcess());

        $processContext = $this->getProcessContext();
        $processContext
            ->method('isValid')
            ->willReturn(true);

        $this->coordinator = $this->createCoordinator($router, $processBuilder, $processContext);
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder(ProcessScenarioInterface::class)->getMock());

        $result = $this->coordinator->forward('scenarioOne', 'unexpectedTypeStep');
        self::assertInstanceOf(Response::class, $result);
    }

    /**
     * @test
     *
     */
    public function shouldShowFormWhenForwardReturnsUnexpectedType()
    {
        $this->expectException(\RuntimeException::class);
        $router = $this->getRouter();

        $processBuilder = $this->getProcessBuilder($this->getProcess());

        $processContext = $this->getProcessContext();
        $processContext
            ->method('isValid')
            ->willReturn(true);

        $this->coordinator = $this->createCoordinator($router, $processBuilder, $processContext);
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder(ProcessScenarioInterface::class)->getMock());

        $result = $this->coordinator->forward('scenarioOne', 'unexpectedTypeStep');
        self::assertInstanceOf(Response::class, $result);
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Coordinator\Coordinator::forward
     */
    public function shouldShowReturnResponseWhenStepIsNotCompleted()
    {
        $router = $this->getRouter();

        $processBuilder = $this->getProcessBuilder($this->getProcess());

        $processContext = $this->getProcessContext();
        $processContext->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->coordinator = $this->createCoordinator($router, $processBuilder, $processContext);
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder('Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface')->getMock());

        $result = $this->coordinator->forward('scenarioOne', 'notForwardStep');
        self::assertInstanceOf('Symfony\Component\HttpFoundation\Response', $result);
    }

    /**
     * @test
     *
     *
     */
    public function shouldNotRegisterScenarioAgain()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Process scenario with alias \"scenarioOne\" is already registered");
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder(ProcessScenarioInterface::class)->getMock());
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder(ProcessScenarioInterface::class)->getMock());
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Coordinator\Coordinator::forward
     */
    public function shouldRedirectToNextStepDisplayActionWhenStepIsCompleted()
    {
        $router = $this->getRouter(
            'sylius_flow_display',
             array(
                'scenarioAlias' => 'scenarioOne',
                'stepName' => 'nextStepName'
            ),
            'http://someurl.dev/step/scenarioOne/nextStepName'
        );

        $processBuilder = $this->getProcessBuilder($this->getProcess());

        $processContext = $this->getProcessContext();
        $processContext->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue(true));
        $processContext->expects($this->any())
            ->method('getNextStep')
            ->will($this->returnValue(
                $this->getStep('nextStepName')
            ));
        $processContext->expects($this->any())
            ->method('getStepHistory')
            ->will($this->returnValue(array()));

        $this->coordinator = $this->createCoordinator($router, $processBuilder, $processContext);
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder('Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface')->getMock());

        $response = $this->coordinator->forward('scenarioOne', 'someStepName');

        self::assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        self::assertEquals('http://someurl.dev/step/scenarioOne/nextStepName', $response->getTargetUrl());
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Coordinator\Coordinator::forward
     */
    public function shouldRedirectToNextStepDisplayActionWhenStepProceeds()
    {
        $router = $this->getRouter(
            'sylius_flow_display',
            array(
                'scenarioAlias' => 'scenarioOne',
                'stepName' => 'nextStepName'
            ),
            'http://someurl.dev/step/scenarioOne/nextStepName'
        );

        $processBuilder = $this->getProcessBuilder($this->getProcess());

        $processContext = $this->getProcessContext();
        $processContext->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue(true));
        $processContext->expects($this->any())
            ->method('getNextStep')
            ->will($this->returnValue(
                       $this->getStep('nextStepName')
                   ));
        $processContext->expects($this->any())
            ->method('getStepHistory')
            ->will($this->returnValue(array()));

        $this->coordinator = $this->createCoordinator($router, $processBuilder, $processContext);
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder('Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface')->getMock());

        $response = $this->coordinator->forward('scenarioOne', 'goToNextStep');

        self::assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        self::assertEquals('http://someurl.dev/step/scenarioOne/nextStepName', $response->getTargetUrl());
    }

    /**
     * @test
     * @covers Sylius\Bundle\FlowBundle\Process\Coordinator\Coordinator::forward
     */
    public function shouldDoProcessRedirectWhenLastStepIsCompleted()
    {

        $router = $this->getRouter(
            'http://localhost/processRedirect',
            array(),
            'http://localhost/processRedirect'
        );

        $processBuilder = $this->getProcessBuilder($this->getProcess());

        $processContext = $this->getProcessContext();
        $processContext
            ->method('isValid')
            ->willReturn(true);
        $processContext->expects(self::once())
            ->method('close');
        $processContext->expects(self::once())
            ->method('isLastStep')
            ->willReturn(true);

        $this->coordinator = $this->createCoordinator($router, $processBuilder, $processContext);
        $this->coordinator->registerScenario('scenarioOne', $this->getMockBuilder('Sylius\Bundle\FlowBundle\Process\Scenario\ProcessScenarioInterface')->getMock());

        $response = $this->coordinator->forward('scenarioOne', 'someStepName');

        self::assertInstanceOf('Symfony\Component\HttpFoundation\RedirectResponse', $response);
        self::assertEquals('http://localhost/processRedirect', $response->getTargetUrl());
    }

    private function createCoordinator($router, $processBuilder, $processContext)
    {
        return new Coordinator(
            $router,
            $processBuilder,
            $processContext
        );
    }

    private function getRouter($route = '', $secondParam = array(), $url = 'http://someurl.dev')
    {
        $router = $this->getMockBuilder('Symfony\Component\Routing\RouterInterface')->getMock();
        $router->expects($this->any())
            ->method('generate')
            ->with($this->equalTo($route), $this->equalTo($secondParam))
            ->will($this->returnValue($url));

        return $router;
    }

    private function getProcessBuilder($process)
    {
        $builder = $this->getMockBuilder('Sylius\Bundle\FlowBundle\Process\Builder\ProcessBuilderInterface')->getMock();
        $builder->expects($this->any())
            ->method('build')
            ->will($this->returnValue(
                $process
            ));

        return $builder;
    }

    private function getProcessContext()
    {
        return $this->getMockBuilder(ProcessContextInterface::class)->getMock();
    }

    private function getProcess()
    {
        $process = $this->getMockBuilder('Sylius\Bundle\FlowBundle\Process\ProcessInterface')->getMock();
        $process->expects($this->any())
            ->method('getFirstStep')
            ->will($this->returnValue(
                $this->getStep('firstStepName')
            ));
        $process->expects($this->any())
            ->method('getStepByName')
            ->will($this->returnValueMap(
                array(
                    array('someStepName', $this->getStep('someStepName')),
                    array('notForwardStep', $this->getStep('notForwardStep')),
                    array('unexpectedTypeStep', $this->getStep('unexpectedTypeStep')),
                    array('goToNextStep', $this->getStep('goToNextStep')),
                )
               ));
        $process->expects($this->any())
            ->method('setScenarioAlias')
            ->with($this->equalTo('scenarioOne'));
        $process->expects($this->any())
            ->method('getScenarioAlias')
            ->will($this->returnValue('scenarioOne'));
        $process->expects($this->any())
            ->method('getRedirect')
            ->will($this->returnValue('http://localhost/processRedirect'));

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
            ->will($this->returnValue(new Response()));
        switch ($name) {
            case 'notForwardStep':
                $step->expects($this->any())
                    ->method('forwardAction')
                    ->will($this->returnValue(new Response()));
                break;
            case 'unexpectedTypeStep':
                $step->expects($this->any())
                    ->method('forwardAction')
                    ->will($this->returnValue("dummy"));
                break;
            case 'goToNextStep':
                $step->expects($this->any())
                    ->method('forwardAction')
                    ->will($this->returnValue(new ActionResult('someStepName')));
                break;
            default:
                $step->expects($this->any())
                    ->method('forwardAction')
                    ->will($this->returnValue(new ActionResult()));
        }

        return $step;
    }

}
