<?php

namespace Akeneo\Pim\Enrichment\Bundle\Storage\Sql\Product;

use Doctrine\DBAL\Connection;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @copyright 2022 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ChunkProductUuids
{
    public function __construct(private readonly Connection $dbConnection)
    {
    }

    /**
     * Naïve implementation to chunk elements by groups that does not exceed a given size in term of raw values.
     * The main purpose is to adapt the size of the batch dynamically to not reach the PHP memory limit.
     *
     * It is not optimized to balance the size between the groups, as the solution would be more complex.
     * @see https://en.wikipedia.org/wiki/Bin_packing_problem
     */
    public function byRawValuesSize(array $productUuids, int $maxSizeInBytesPerChunk)
    {
        // ORDER BY is not in dedicated query to avoid Out of sort memory exception
        // because raw_values are too big in the buffer, even if not useful for the ordering
        // DISTINCT is trick to force to materialize the CTE before ordering the results
        $query = <<<SQL
            WITH product_size as (
                SELECT DISTINCT uuid, JSON_STORAGE_SIZE(raw_values) as size 
                FROM pim_catalog_product
                WHERE uuid IN (:uuids)
            )
            SELECT uuid, size
            FROM product_size
            ORDER BY FIELD(uuid, :uuids)
        SQL;

        $results = $this->dbConnection->executeQuery(
            $query,
            [
                'uuids' => array_map(fn (UuidInterface $uuid): string => $uuid->getBytes(), $productUuids),
            ],
            ['uuids' => \Doctrine\DBAL\Connection::PARAM_STR_ARRAY]
        );

        $chunks = [];
        $chunk = [];
        $chunkSize = 0;

        foreach ($results as $row) {
            if ($chunkSize + $row['size'] < $maxSizeInBytesPerChunk) {
                $chunk[] = Uuid::fromBytes($row['uuid']);
                $chunkSize += $row['size'];
            } else {
                $chunks[] = $chunk;
                $chunk = [Uuid::fromBytes($row['uuid'])];
                $chunkSize = $row['size'];
            }
        }

        $chunks[] = $chunk;

        return $chunks;
    }
}
