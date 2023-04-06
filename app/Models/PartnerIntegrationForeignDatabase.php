<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\PartnerIntegrationForeignDatabase
 *
 * @property        int                                                                     $id
 * @property        int                                                                     $control_id
 * @property        int                                                                     $foreign_control_id
 * @property        string|null                                                             $foreign_server_name
 * @property        string|null                                                             $schema_name
 * @property        bool|null                                                               $is_deleted
 * @property        \Illuminate\Support\Carbon|null                                         $created_at
 * @property        \Illuminate\Support\Carbon|null                                         $updated_at
 * @property        int|null                                                                $product_id
 * @property        \Illuminate\Support\Carbon|null                                         $deleted_at
 * @property        \App\Models\PartnerIntegration|null                                     $database
 * @property        \App\Models\PartnerIntegration|null                                     $foreign_database
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase dataLake()
 * @method   static \Database\Factories\PartnerIntegrationForeignDatabaseFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase newQuery()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationForeignDatabase    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase query()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase studio()
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase whereControlId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase whereForeignControlId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase whereForeignServerName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase whereProductId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase whereSchemaName($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|PartnerIntegrationForeignDatabase whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationForeignDatabase    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|PartnerIntegrationForeignDatabase    withoutTrashed()
 * @mixin \Eloquent
 */
class PartnerIntegrationForeignDatabase extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_partner_integration_foreign_databases';

    public function database()
    {
        return $this->belongsTo(PartnerIntegration::class, 'control_id');
    }

    public function foreign_database()
    {
        return $this->belongsTo(PartnerIntegration::class, 'foreign_control_id');
    }

    public static function scopeDataLake($query)
    {
        $data_lake = Product::where('name', 'datalake')->first();

        return $query->where('product_id', $data_lake->id);
    }

    public static function scopeStudio($query)
    {
        $studio = Product::where('name', 'studio')->first();

        return $query->where('product_id', $studio->id);
    }

    public static function schemas(PartnerIntegration $database, string $type)
    {
        return self::$type()
            ->where('control_id', $database->id)
            ->get()
            ->mapWithKeys(function ($foreign_database) {
                return [$foreign_database->foreign_database->database => $foreign_database->schema_name];
            })
            ->toArray();
    }
}
