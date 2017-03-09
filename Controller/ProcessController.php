<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\FlowBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Process controller.
 *
 * @author Paweł Jędrzejewski <pjedrzejewski@diweb.pl>
 */
class ProcessController implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    /**
     * @var string Parameter of an array of keys that have to be merged from $request->attributes to $request->query
     */
    const ATTRIBUTES_TO_BE_PASSED_PARAMETER = 'sylius.attributeparameterstobepassed';

    /**
     * Build and start process for given scenario.
     * This action usually redirects to first step.
     *
     * @param Request $request
     * @param string  $scenarioAlias
     *
     * @return Response
     */
    public function startAction(Request $request, $scenarioAlias)
    {
        $coordinator = $this->container->get('sylius.process.coordinator');

        if ($this->container->hasParameter(self::ATTRIBUTES_TO_BE_PASSED_PARAMETER)) {
            $attributeParametersToBePassed = $this->container->getParameter(self::ATTRIBUTES_TO_BE_PASSED_PARAMETER);

            foreach ($attributeParametersToBePassed as $attributeParameter) {

                // If key is given in attributes and is not already set in querystring, merge it
                if (false === $request->query->has($attributeParameter) && $request->attributes->has($attributeParameter)) {
                    $request->query->set($attributeParameter, $request->attributes->get($attributeParameter));
                }
            }
        }

        return $coordinator->start($scenarioAlias, $request->query);
    }

    /**
     * Execute display action of given step.
     *
     * @param Request $request
     * @param string  $scenarioAlias
     * @param string  $stepName
     *
     * @return Response
     */
    public function displayAction(Request $request, $scenarioAlias, $stepName)
    {
        $this->container->get('sylius.process.context')->setRequest($request);

        $coordinator = $this->container->get('sylius.process.coordinator');

        try {
            return $coordinator->display($scenarioAlias, $stepName, $request->query);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException('The step you are looking for is not found.', $e);
        }
    }

    /**
     * Execute continue action of given step.
     *
     * @param Request $request
     * @param string  $scenarioAlias
     * @param string  $stepName
     *
     * @return Response
     */
    public function forwardAction(Request $request, $scenarioAlias, $stepName)
    {
        $this->container->get('sylius.process.context')->setRequest($request);

        $coordinator = $this->container->get('sylius.process.coordinator');

        return $coordinator->forward($scenarioAlias, $stepName);
    }
}
