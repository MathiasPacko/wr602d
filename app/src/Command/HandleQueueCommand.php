<?php

namespace App\Command;

use App\Entity\Generation;
use App\Entity\MergeQueue;
use App\Repository\MergeQueueRepository;
use App\Repository\UserContactRepository;
use App\Service\GotenbergService;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:handle-queue',
    description: 'Process pending PDF merge requests from the queue',
)]
class HandleQueueCommand extends Command
{
    public function __construct(
        private MergeQueueRepository $mergeQueueRepository,
        private EntityManagerInterface $entityManager,
        private GotenbergService $gotenbergService,
        private MailerService $mailerService,
        private UserContactRepository $userContactRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of items to process', 10)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int) $input->getOption('limit');

        $io->title('Processing PDF Merge Queue');

        // Get pending merge requests
        $pendingItems = $this->mergeQueueRepository->findPending($limit);

        if (empty($pendingItems)) {
            $io->success('No pending items in queue.');
            return Command::SUCCESS;
        }

        $io->info(sprintf('Found %d pending merge request(s)', count($pendingItems)));

        $processed = 0;
        $failed = 0;

        foreach ($pendingItems as $item) {
            $io->section(sprintf('Processing merge request #%d', $item->getId()));

            // Mark as processing
            $item->setStatus(MergeQueue::STATUS_PROCESSING);
            $this->entityManager->flush();

            try {
                // Get file paths
                $filePaths = $item->getFilePaths();

                if (count($filePaths) < 2) {
                    throw new \Exception('At least 2 PDF files are required for merge');
                }

                $io->text(sprintf('Merging %d files...', count($filePaths)));

                // Perform merge using Gotenberg
                $pdfContent = $this->gotenbergService->mergePdfsByPath($filePaths);

                // Save the result
                $filename = 'merged_' . uniqid() . '.pdf';
                $resultPath = sys_get_temp_dir() . '/' . $filename;
                file_put_contents($resultPath, $pdfContent);

                $item->setResultPath($resultPath);
                $item->setStatus(MergeQueue::STATUS_COMPLETED);
                $item->setProcessedAt(new \DateTime());

                // Create generation record
                $generation = new Generation();
                $generation->setUser($item->getUser());
                $generation->setType('merge');
                $generation->setFilename($filename);
                $generation->setCreatedAt(new \DateTime());
                $this->entityManager->persist($generation);

                // Send email if requested
                if ($item->isSendEmail()) {
                    $io->text('Sending email to user...');
                    $this->mailerService->sendPdfToUser(
                        $item->getUser(),
                        $pdfContent,
                        $filename,
                        'Fusion PDF'
                    );
                }

                // Send to contacts if any
                $contactIds = $item->getContactIds();
                if (!empty($contactIds)) {
                    foreach ($contactIds as $contactId) {
                        $contact = $this->userContactRepository->find($contactId);
                        if ($contact && $contact->getUser()->getId() === $item->getUser()->getId()) {
                            $io->text(sprintf('Sending to contact: %s', $contact->getEmail()));
                            $this->mailerService->sendPdfToContact(
                                $item->getUser(),
                                $contact,
                                $pdfContent,
                                $filename,
                                'Fusion PDF',
                                null
                            );
                        }
                    }
                }

                $this->entityManager->flush();

                $io->success(sprintf('Merge request #%d completed successfully', $item->getId()));
                $processed++;

            } catch (\Exception $e) {
                $item->setStatus(MergeQueue::STATUS_FAILED);
                $item->setErrorMessage($e->getMessage());
                $item->setProcessedAt(new \DateTime());
                $this->entityManager->flush();

                $io->error(sprintf('Merge request #%d failed: %s', $item->getId(), $e->getMessage()));
                $failed++;
            }
        }

        $io->newLine();
        $io->success(sprintf('Queue processing complete. Processed: %d, Failed: %d', $processed, $failed));

        return Command::SUCCESS;
    }
}
