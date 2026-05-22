<?php

declare(strict_types=1);

namespace Oro\Bundle\ZendeskBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\ZendeskBundle\Entity\Repository\ZendeskRestTransportRepository;
use Oro\Bundle\ZendeskBundle\Entity\ZendeskRestTransport;
use Oro\Bundle\ZendeskBundle\Provider\OAuth\AccessTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Proactively refreshes Zendesk OAuth tokens to prevent expiration
 */
#[AsCommand(
    name: 'oro:cron:zendesk:refresh-tokens',
    description: 'Refreshes Zendesk OAuth access tokens.'
)]
class RefreshOAuthTokensCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly AccessTokenManagerInterface $tokenManager,
        private readonly string $cronDefinition,
        private readonly int $refreshThresholdDays,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    #[\Override]
    public function getDefaultDefinition(): string
    {
        return $this->cronDefinition;
    }

    #[\Override]
    public function isActive(): bool
    {
        $count = $this->getIntegrationRepository()
            ->countAuthorizedIntegrations();

        return $count > 0;
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setHelp(sprintf(
            <<<'HELP'
The <info>%%command.name%%</info> command proactively refreshes OAuth access tokens
for Zendesk integrations to prevent token expiration.

  <info>php %%command.full_name%%</info>

This command:
- Queries integrations with OAuth tokens that haven't been refreshed in %d+ days
- Refreshes access tokens using the refresh token
- Updates the last refresh timestamp
- Logs successes and failures for monitoring

The command runs automatically via cron schedule: %s
HELP,
            $this->refreshThresholdDays,
            $this->cronDefinition,
        ));
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('<info>Starting Zendesk OAuth token refresh...</info>');

        /** @var ZendeskRestTransportRepository $repository */
        $repository = $this->doctrine->getRepository(ZendeskRestTransport::class);
        $transports = $repository->findTransportsNeedingTokenRefresh(
            $this->refreshThresholdDays
        );

        if (empty($transports)) {
            $output->writeln('<comment>No transports need token refresh at this time.</comment>');
            return Command::SUCCESS;
        }

        $output->writeln(sprintf('Found %d transport(s) requiring token refresh.', count($transports)));

        $refreshed = 0;
        $failed = 0;

        foreach ($transports as $transport) {
            try {
                $this->tokenManager->refreshAccessToken($transport);
                $refreshed++;

                $this->logger->info('Zendesk OAuth token refreshed', [
                    'transport_id' => $transport->getId(),
                    'previous_refresh' => $transport->getOauthLastRefreshAt()?->format('Y-m-d H:i:s'),
                ]);

                $output->writeln(sprintf(
                    '  <info>✓</info> Refreshed token for transport #%d',
                    $transport->getId()
                ));
            } catch (\Exception $e) {
                $failed++;

                $this->logger->error('Failed to refresh Zendesk OAuth token', [
                    'transport_id' => $transport->getId(),
                    'previous_refresh' => $transport->getOauthLastRefreshAt()?->format('Y-m-d H:i:s'),
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]);

                $output->writeln(sprintf(
                    '  <error>✗</error> Failed to refresh token for transport #%d: %s',
                    $transport->getId(),
                    $e->getMessage()
                ));
            }
        }

        $output->writeln('');
        $output->writeln(sprintf('<info>Token refresh complete:</info>'));
        $output->writeln(sprintf('  - Successfully refreshed: %d', $refreshed));
        $output->writeln(sprintf('  - Failed: %d', $failed));

        return $failed > 0
            ? Command::FAILURE
            : Command::SUCCESS;
    }

    private function getIntegrationRepository(): ObjectRepository
    {
        return $this->doctrine->getRepository(ZendeskRestTransport::class);
    }
}
