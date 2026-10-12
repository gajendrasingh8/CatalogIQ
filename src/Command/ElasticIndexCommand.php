<?php

namespace App\Command;

use App\Repository\ProductRepository;
use App\Service\ElasticsearchService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:elastic-index',
    description: 'Create the Elasticsearch index and bulk-index all products from MySQL.',
)]
class ElasticIndexCommand extends Command
{
    public function __construct(
        private readonly ElasticsearchService $elasticsearchService,
        private readonly ProductRepository    $productRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'reset',
                'r',
                InputOption::VALUE_NONE,
                'Drop and recreate the index before indexing (full re-index)'
            )
            ->addOption(
                'batch-size',
                'b',
                InputOption::VALUE_OPTIONAL,
                'Number of products to index per batch',
                100
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('CatalogIQ — Elasticsearch Indexer');

        // --- Check ES availability ---
        if (!$this->elasticsearchService->isAvailable()) {
            $io->error([
                'Elasticsearch is not reachable!',
                'Make sure Elasticsearch is running at ' . $_ENV['ELASTICSEARCH_URL'] ?? 'http://localhost:9200',
            ]);
            return Command::FAILURE;
        }

        $io->success('Elasticsearch is reachable.');

        // --- Reset or create index ---
        $reset = $input->getOption('reset');

        if ($reset) {
            $io->section('Resetting index (drop + recreate)...');
            $this->elasticsearchService->resetIndex();
            $io->writeln('  ✓ Index reset complete.');
        } else {
            $io->section('Ensuring index exists...');
            $this->elasticsearchService->createIndex();
            $io->writeln('  ✓ Index ready.');
        }

        // --- Fetch all products ---
        $io->section('Fetching products from MySQL...');
        $products = $this->productRepository->findAll();
        $total    = count($products);

        if ($total === 0) {
            $io->warning('No products found in MySQL. Run `php bin/console app:seed-products` first.');
            return Command::SUCCESS;
        }

        $io->writeln("  Found <info>{$total}</info> products to index.");

        // --- Progress bar ---
        $progressBar = new ProgressBar($output, $total);
        $progressBar->setFormat(
            ' %current%/%max% [%bar%] %percent:3s%% — <info>%indexed%</info> indexed — elapsed: %elapsed:6s%'
        );
        $progressBar->setMessage('0', 'indexed');
        $progressBar->start();

        // --- Bulk index with progress callback ---
        $batchSize = (int) $input->getOption('batch-size');
        $indexed   = 0;

        $chunks = array_chunk($products, $batchSize);
        foreach ($chunks as $chunk) {
            $params = ['body' => []];

            foreach ($chunk as $product) {
                $params['body'][] = [
                    'index' => [
                        '_index' => ElasticsearchService::INDEX,
                        '_id'    => (string) $product->getId(),
                    ],
                ];
                $params['body'][] = $product->toElasticDocument();
            }

            // Use the service's internal client via bulk
            $this->elasticsearchService->bulkIndex($chunk);
            $indexed += count($chunk);

            $progressBar->setMessage((string) $indexed, 'indexed');
            $progressBar->advance(count($chunk));
        }

        $progressBar->finish();
        $output->writeln('');

        // --- Summary ---
        $io->success("Successfully indexed {$indexed} / {$total} products into Elasticsearch.");
        $io->table(
            ['Index', 'Total Products', 'Status'],
            [[ElasticsearchService::INDEX, $indexed, '✅ Complete']]
        );

        return Command::SUCCESS;
    }
}
