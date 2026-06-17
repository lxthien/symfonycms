<?php

namespace AppBundle\Command;

use AppBundle\Entity\News;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Publishes scheduled posts whose scheduledAt time has passed.
 *
 * Run this command via cron every minute:
 *   * * * * * cd /path/to/project && php bin/console app:publish-scheduled >> var/log/scheduled.log 2>&1
 */
class ScheduledPublishCommand extends Command
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct('app:publish-scheduled');
        $this->em = $em;
    }

    protected function configure()
    {
        $this->setDescription('Auto-publish news posts that are scheduled and past their scheduledAt time.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $now = new \DateTime('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));

        $scheduledPosts = $this->em->getRepository(News::class)->createQueryBuilder('n')
            ->where('n.status = :status')
            ->andWhere('n.scheduledAt <= :now')
            ->setParameter('status', News::STATUS_SCHEDULED)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        if (empty($scheduledPosts)) {
            $io->text('No scheduled posts to publish.');
            return 0;
        }

        $count = 0;
        foreach ($scheduledPosts as $post) {
            $post->setStatus(News::STATUS_PUBLISHED);
            $post->setPublishedAt($now);
            $count++;
        }

        $this->em->flush();

        $io->success(sprintf('Published %d scheduled post(s).', $count));

        return 0;
    }
}
