<?php

declare(strict_types=1);

namespace AkeneoTest\Pim\Enrichment\Integration\Storage\ElasticsearchAndSql\ProductAndProductModel;

use Akeneo\Pim\Enrichment\Bundle\Storage\ElasticsearchAndSql\ProductAndProductModel\GetDeletedProductModelDocumentIds;
use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Test\Integration\Configuration;
use Akeneo\Test\Integration\TestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use PHPUnit\Framework\Assert;

/**
 * @copyright 2023 Akeneo SAS (https://www.akeneo.com)
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
final class GetDeleteProductModelDocumentIdsIntegration extends TestCase
{
    /** @test */
    public function it_returns_nothing_if_product_model_was_deleted(): void
    {
        $this->assertDeletedProductModelDocuments([]);
    }

    /** @test */
    public function it_fetches_deleted_product_model_document_ids(): void
    {
        $deletedIdentifiers = $this->deleteProductsModelFromDb(10);
        $this->assertDeletedProductModelDocuments($deletedIdentifiers);
    }

    private function deleteProductsModelFromDb(int $count): array
    {
        $randomIdentifiers = $this->getConnection()->fetchFirstColumn(
            'SELECT code FROM pim_catalog_product_model ORDER BY RAND() LIMIT :limit',
            ['limit' => $count],
            ['limit' => ParameterType::INTEGER]
        );

        $this->getConnection()->executeStatement(
            'DELETE FROM pim_catalog_product_model WHERE code IN (:randomIdentifiers)',
            [
                'randomIdentifiers' => $randomIdentifiers,
            ],
            ['randomIdentifiers' => Connection::PARAM_STR_ARRAY]
        );

        return $randomIdentifiers;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->get('akeneo_integration_tests.fixture.loader.product_and_product_model_with_removed_attribute')->load();

        for ($i = 0; $i < 30; $i++) {
            $this->createProductModel(sprintf('product_model_identifier_%s', $i), 'a_family_variant', []);
        }
        $this->get('akeneo_elasticsearch.client.product_and_product_model')->refreshIndex();
    }

    private function createProductModel(string $code, string $familyVariantCode, array $data): ProductModelInterface
    {
        $data = \array_merge(['code' => $code, 'family_variant' => $familyVariantCode], $data);
        $productModel = $this->get('pim_catalog.factory.product_model')->create();
        $this->get('pim_catalog.updater.product_model')->update($productModel, $data);

        $violations = $this->get('pim_catalog.validator.product')->validate($productModel);
        Assert::assertSame(0, $violations->count(), (string) $violations);
        $this->get('pim_catalog.saver.product_model')->save($productModel);

        return $productModel;
    }

    private function assertDeletedProductModelDocuments(array $expectedIdentifiers): void
    {
        $getDeletedProductModelDocumentIdentifiers = $this->get(GetDeletedProductModelDocumentIds::class);
        $deletedProductModelDocumentIdentifiers = \iterator_to_array($getDeletedProductModelDocumentIdentifiers());

        Assert::assertEqualsCanonicalizing(\sort($expectedIdentifiers), \sort($deletedProductModelDocumentIdentifiers));
    }

    private function getConnection(): Connection
    {
        return $this->get('database_connection');
    }

    protected function getConfiguration(): Configuration
    {
        return $this->catalog->useMinimalCatalog();
    }
}
