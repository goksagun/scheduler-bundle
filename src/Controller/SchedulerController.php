<?php

declare(strict_types=1);

namespace Goksagun\SchedulerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/scheduler")
 */
class SchedulerController extends AbstractController
{
    /**
     * @Route("/run", methods={"GET"})
     */
    public function run(Request $request, KernelInterface $kernel): Response
    {
        $async = $request->query->get('async');
        $resource = $request->query->get('resource');

        $command = ['command' => 'scheduler:run'];

        if ($async !== null) {
            $command['--async'] = $async;
        }

        if ($resource !== null) {
            $command['--resource'] = $resource;
        }

        try {

            $application = new Application($kernel);
            $application->setAutoExit(false);

            $input = new ArrayInput($command);
            $output = new BufferedOutput();
            $application->run($input, $output);

            return new Response($output->fetch());

        } catch (\Exception $e) {
            return new Response($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
