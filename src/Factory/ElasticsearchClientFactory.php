<?php

namespace App\Factory;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

class ElasticsearchClientFactory
{
    /**
     * Build and return an Elasticsearch Client configured with the given host URL.
     */
    public static function create(string $url): Client
    {
        return ClientBuilder::create()
            ->setHosts([$url])
            ->build();
    }
}
