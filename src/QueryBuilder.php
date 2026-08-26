<?php
declare(strict_types=1);

/**
 * This file is part of web-fu/simple-repository
 *
 * @copyright Web-Fu <info@web-fu.it>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFu\SimpleRepository;

use WebFu\SimpleRepository\Exception\RepositoryException;

class QueryBuilder
{
    private string $query;
    /**
     * @var mixed[]
     */
    private array $params = [];

    /**
     * @param array<string, mixed> $params
     */
    public function __construct(string $query, array $params = []){
        $this->query  = $query;
        $this->params = $params;
    }

    /**
     * @return array{string, array<non-falsy-string, mixed>}
     */
    public function getQueryAndData(): array
    {
        $temp = $this->query;
        preg_match_all('/:\w+/', $temp, $matches);
        $params = $matches[0];

        $neededParams   = [];
        $formattedQuery = '';
        foreach ($params as $param) {
            $pos = (int) strpos($temp, $param);
            $formattedQuery .= substr($temp, 0, $pos);
            $neededParam                = $param . '_' . substr_count($formattedQuery, $param);
            $neededParams[$neededParam] = $param;
            $formattedQuery .= $neededParam;
            $temp = substr($temp, $pos + strlen($param));
        }
        $formattedQuery .= $temp;

        $data = [];
        // Add : if needed
        /** @var string $key */
        foreach ($this->params as $key => $value) {
            if (':' !== substr($key, 0, 1)) {
                $key = ':'.$key;
            }
            $data[$key] = $value;
        }

        $formattedData = [];
        foreach ($neededParams as $neededParam => $param) {
            if (!array_key_exists($param, $data)) {
                continue;
            }
            $formattedData[$neededParam] = $data[$param];
        }

        if ($missingData = array_diff(array_keys($neededParams), array_keys($formattedData))) {
            throw new RepositoryException('Missing Data to complete query:'.PHP_EOL.implode(', ', $missingData), 500);
        }

        return [
             $formattedQuery,
             $formattedData,
        ];
    }
}