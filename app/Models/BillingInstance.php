<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingInstance extends Model
{
    protected $fillable = [
        'tenant_code',
        'name',
        'domain_url',
        'api_key',
        'callback_url',
        'is_active',
        'db_host',
        'db_port',
        'db_database',
        'db_username',
        'db_password',
    ];

    /**
     * Dynamically configure and get a database connection for this billing instance.
     *
     * @return \Illuminate\Database\Connection|null
     */
    public function getDatabaseConnection()
    {
        if (empty($this->db_database)) {
            return null;
        }

        $connectionName = 'billing_instance_' . $this->id;

        config([
            "database.connections.{$connectionName}" => [
                'driver'         => 'mysql',
                'host'           => $this->db_host ?: env('BILLING_CI3_DB_HOST', '127.0.0.2'),
                'port'           => $this->db_port ?: env('BILLING_CI3_DB_PORT', '3306'),
                'database'       => $this->db_database,
                'username'       => $this->db_username ?: env('BILLING_CI3_DB_USERNAME', 'root'),
                'password'       => $this->db_password ?? env('BILLING_CI3_DB_PASSWORD', ''),
                'charset'        => 'utf8',
                'collation'      => 'utf8_general_ci',
                'prefix'         => '',
                'prefix_indexes' => true,
                'strict'         => false,
                'engine'         => null,
            ]
        ]);

        return \Illuminate\Support\Facades\DB::connection($connectionName);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'billing_node_id');
    }
}
