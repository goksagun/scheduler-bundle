<?php

namespace Goksagun\SchedulerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/scheduler")
 */
class SchedulerController extends AbstractController
{
    /**
     * @Route("/run", methods={"POST"})
     */
    public function run(KernelInterface $kernel)
    {
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'scheduler:run'
        ]);

        $output = new BufferedOutput();
        $application->run($input, $output);

        return new Response($output->fetch());
    }
}
