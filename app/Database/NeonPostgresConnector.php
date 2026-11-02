<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

class NeonPostgresConnector extends PostgresConnector
{
    protected function addServerOptions($dsn, array $config)
    {
        $dsn = parent::addServerOptions($dsn, $config);
        $host = $config['host'] ?? null;

        // The connection factory supplies the host after parsing DB_URL and
        // selecting a host. Never derive this value from an HTTP request.
        if (! is_string($host) || ! str_ends_with(strtolower($host), '.neon.tech')) {
            return $dsn;
        }

        $endpoint = explode('.', strtolower($host), 2)[0];
        $endpoint = preg_replace('/-pooler\z/', '', $endpoint);

        if (! preg_match('/\Aep-[a-z0-9]+(?:-[a-z0-9]+)*\z/', $endpoint)) {
            return $dsn;
        }

        // Neon accepts a bare endpoint option for clients without SNI. This
        // preserves the normal password/SCRAM authentication and SSL settings.
        // Laravel's server_options array would prefix it with "-c", so insert
        // the routing option directly into libpq's options instead.
        $routingOption = 'endpoint='.$endpoint;

        if (empty($config['server_options'])) {
            return $dsn.";options='".$routingOption."'";
        }

        $optionsStart = strrpos($dsn, ";options='");

        return substr_replace($dsn, $routingOption.' ', $optionsStart + strlen(";options='"), 0);
    }
}
