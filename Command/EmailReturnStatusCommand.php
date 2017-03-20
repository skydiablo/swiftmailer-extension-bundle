<?php

namespace SkyDiablo\SwiftmailerExtensionBundle\Command;

use SkyDiablo\SwiftmailerExtensionBundle\Handler\aws\EmailReturnStatusHandler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Volker von Hoesslin <volker@oksnap.me>
 * Class EmailReturnStatusCommand
 */
class EmailReturnStatusCommand extends ContainerAwareCommand
{

    const NAME = 'skydiablo.swiftmailer-extension.email-return-status';
    const OPTION_COUNT = 'count';
    const OPTION_LONG_POLLING_TIMEOUT = 'long-polling-timeout';

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addOption(self::OPTION_COUNT, null, InputOption::VALUE_OPTIONAL, null, 10)
            ->addOption(self::OPTION_LONG_POLLING_TIMEOUT, null, InputOption::VALUE_OPTIONAL, null, EmailReturnStatusHandler::DEFAULT_AWS_SQS_LONG_POLLING_TIMEOUT);
    }

    /**
     * Executes the current command.
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Return email status handler');

        $optionCount = $input->getOption(self::OPTION_COUNT);
        $optionLongPollingTimeout = $input->getOption(self::OPTION_LONG_POLLING_TIMEOUT);

        $io->text([
            sprintf('Count: %d', $optionCount),
            sprintf('Long polling timeout: %d seconds', $optionLongPollingTimeout),
            'running...'
        ]);

        $emailReturnStatusHandler = $this->getContainer()->get('sky_diablo_swiftmailer_extension.handler_aws.email_return_status_handler');
        $handledCount = $emailReturnStatusHandler->run($optionCount, $optionLongPollingTimeout);

        $io->text([
            sprintf('Handled count: %d', $handledCount),
            'done'
        ]);
    }


}