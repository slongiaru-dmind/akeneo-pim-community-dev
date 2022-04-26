<?php

declare(strict_types=1);

namespace Akeneo\Pim\Automation\DataQualityInsights\Infrastructure\Elasticsearch\Query;

use Akeneo\Pim\Automation\DataQualityInsights\Application\ProductEntityIdFactoryInterface;
use Akeneo\Pim\Automation\DataQualityInsights\Domain\Query\ProductEvaluation\GetUpdatedProductUuidsQueryInterface;
use Akeneo\Pim\Automation\DataQualityInsights\Domain\ValueObject\ProductEntityIdCollection;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductInterface;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Tool\Bundle\ElasticsearchBundle\Client;
use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @copyright 2020 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class GetUpdatedProductUuidsQuery implements GetUpdatedProductUuidsQueryInterface
{
    private const PRODUCT_IDENTIFIER_PREFIX = 'product_';
    private const PRODUCT_MODEL_IDENTIFIER_PREFIX = 'product_model_';

    public function __construct(
        private Client                          $esClient,
        private string                          $documentType,
        private ProductEntityIdFactoryInterface $idFactory,
        private Connection $connection
    ) {
    }

    /**
     * @return \Generator<int, ProductEntityIdCollection>
     */
    public function since(\DateTimeImmutable $updatedSince, int $bulkSize): \Generator
    {
        if ($this->documentType !== ProductModelInterface::class && $this->documentType !== ProductInterface::class) {
            throw new \InvalidArgumentException(sprintf('Invalid type %s', $this->documentType));
        }

        $query = [
            'bool' => [
                'must' => [
                    [
                        'term' => [
                            'document_type' => $this->documentType
                        ],
                    ],
                    [
                        'range' => [
                            'updated' => [
                                'gt' => $updatedSince->setTimezone(new \DateTimeZone('UTC'))->format('c')
                            ],
                        ]
                    ],
                ],
            ],
        ];

        $totalProducts = $this->countUpdatedProducts($query);

        $searchQuery = [
            '_source' => ['id'],
            'size' => $bulkSize,
            'sort' => ['_id' => 'asc'],
            'query' => $query
        ];

        $result = $this->esClient->search($searchQuery);
        $searchAfter = [];
        $returnedProducts = 0;

        while (!empty($result['hits']['hits'])) {
            $productIds = [];
            $previousSearchAfter = $searchAfter;
            foreach ($result['hits']['hits'] as $product) {
                $productIds[] = $this->formatProductId($product);
                $searchAfter = $product['sort'] ?? $searchAfter;
            }

            yield $this->idFactory->createCollection($productIds);

            $returnedProducts += count($productIds);
            $result = $returnedProducts < $totalProducts && $searchAfter !== $previousSearchAfter
                ? $this->searchAfter($searchQuery, $searchAfter)
                : [];
        }
    }

    private function searchAfter(array $query, array $searchAfter): array
    {
        if (!empty($searchAfter)) {
            $query['search_after'] = $searchAfter;
        }

        return $this->esClient->search($query);
    }

    private function formatProductId(array $productData): string
    {
        if (!isset($productData['_source']['id'])) {
            throw new \Exception('No id not found in source when searching updated products');
        }

        $identifierPrexis = $this->documentType === ProductModelInterface::class
            ? self::PRODUCT_MODEL_IDENTIFIER_PREFIX
            : self::PRODUCT_IDENTIFIER_PREFIX;

        $productId =  \str_replace($identifierPrexis, '', $productData['_source']['id']);
        if (Uuid::isValid($productId)) {
            $productId = $this->getProductIdFromUuid(Uuid::fromString($productId));
        }

        return (string) $productId;
    }

    private function countUpdatedProducts(array $query): int
    {
        $count = $this->esClient->count([
            'query' => $query,
        ]);

        return $count['count'] ?? 0;
    }

    private function getProductIdFromUuid(UuidInterface $productUuid): int
    {
        $id = $this->connection
            ->executeQuery('SELECT id FROM pim_catalog_product WHERE uuid = ?', [$productUuid->getBytes()])
            ->fetchOne();

        return $id ? (int) $id : 0;
    }
}