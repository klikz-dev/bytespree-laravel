<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\EventType;
use App\Models\EventMetadata;
use App\Models\PartnerIntegration;

/**
 * App\Models\Event
 *
 * @property        int                                         $id
 * @property        int|null                                    $infrastructure_event_type_id
 * @property        int|null                                    $control_id
 * @property        string|null                                 $job_id
 * @property        string|null                                 $conversion_id
 * @property        string|null                                 $human_id
 * @property        int|null                                    $compute_seconds
 * @property        int|null                                    $records_processed
 * @property        int|null                                    $human_seconds
 * @property        string|null                                 $environment
 * @property        bool|null                                   $is_billable
 * @property        bool|null                                   $is_deleted
 * @property        \Illuminate\Support\Carbon|null             $created_at
 * @property        \Illuminate\Support\Carbon|null             $updated_at
 * @property        \Illuminate\Support\Carbon|null             $deleted_at
 * @method   static \Database\Factories\EventFactory            factory(...$parameters)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event newModelQuery()
 * @method   static \Illuminate\Database\Eloquent\Builder|Event newQuery()
 * @method   static \Illuminate\Database\Query\Builder|Event    onlyTrashed()
 * @method   static \Illuminate\Database\Eloquent\Builder|Event query()
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereComputeSeconds($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereControlId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereConversionId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereCreatedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereDeletedAt($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereEnvironment($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereHumanId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereHumanSeconds($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereInfrastructureEventTypeId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereIsBillable($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereIsDeleted($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereJobId($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereRecordsProcessed($value)
 * @method   static \Illuminate\Database\Eloquent\Builder|Event whereUpdatedAt($value)
 * @method   static \Illuminate\Database\Query\Builder|Event    withTrashed()
 * @method   static \Illuminate\Database\Query\Builder|Event    withoutTrashed()
 * @mixin \Eloquent
 */
class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $table = 'di_events';

    public static function integrationStart(PartnerIntegration $database, array $extra = [])
    {
        $type = EventType::where('name', 'integration start')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => 0
        ]);

        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }
    }

    public static function integrationEnd(PartnerIntegration $database, array $extra = [])
    {
        $type = EventType::where('name', 'integration end')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => 0
        ]);

        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }

        // $this->sendCallbacks($database->id, empty($extra[1]) ? '' : $extra[1], $status);
    }

    public static function buildStart(PartnerIntegration $database, $extra = [])
    {
        $type = EventType::where('name', 'build start')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => 0
        ]);

        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }

        // if (isset($extra[1])) {
        //     $this->view_array = $this->PartnerIntegrationsModel->dropViewsForTable($control_id, $extra[1], 'public', TRUE);
        //     $this->PartnerIntegrationsModel->terminateProcessesLockingTable($control_id, $extra[1]);
        // } else {
        //     $this->view_array = $this->PartnerIntegrationsModel->dropViewsForSchemas($control_id, ['public']);
        // }
    }

    public static function buildEnd(PartnerIntegration $database, $extra = [])
    {
        $type = EventType::where('name', 'build end')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => 0
        ]);
        
        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }

        // todo
        // if (isset($extra[1])) {
        //     $this->PartnerIntegrationsModel->addForeignDataTable($control_id, $extra[1]);
        //     $this->PartnerIntegrationTablesModel->syncMinmumToLast($control_id, $extra[1]);
        // } else {
        //     $this->PartnerIntegrationsModel->rebuildForeignSchemas($control_id);
        // }

        // $this->BP_ProjectsModel->reestablishUserGrants($control_id);
        // $this->PartnerIntegrationsModel->createViews($control_id, $this->view_array);
    }

    public static function reconcileStart(PartnerIntegration $database, $extra = [])
    {
        $type = EventType::where('name', 'reconcile start')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => 0
        ]);

        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }
    }

    public static function reconcileEnd(PartnerIntegration $database, $extra = [])
    {
        $type = EventType::where('name', 'reconcile end')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => 0
        ]);

        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }
    }

    public static function convertStart(PartnerIntegration $database, $extra = [])
    {
        $type = EventType::where('name', 'convert start')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => 0
        ]);

        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }
    }

    public static function convertEnd(PartnerIntegration $database, $extra = [])
    {
        $type = EventType::where('name', 'convert end')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => 0
        ]);

        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }
    }

    public static function reverseIntegrationStart(PartnerIntegration $database, $extra = [])
    {
        $type = EventType::where('name', 'reverse integration start')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => 0
        ]);

        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }
    }

    public static function reverseIntegrationEnd(PartnerIntegration $database, $extra = [])
    {
        $type = EventType::where('name', 'reverse integration end')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => 0
        ]);

        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }
    }

    public static function download(PartnerIntegration $database, $quantity, $extra = [])
    {
        $type = EventType::where('name', 'download')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => $quantity
        ]);

        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }
    }

    public static function upload(PartnerIntegration $database, $quantity, $extra = [])
    {
        $type = EventType::where('name', 'upload')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => $quantity
        ]);

        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }
    }

    public static function compute(PartnerIntegration $database, $quantity, $extra = [])
    {
        $type = EventType::where('name', 'compute')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => $quantity
        ]);

        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }
    }

    public static function convert(PartnerIntegration $database, $quantity, $extra = [])
    {
        $type = EventType::where('name', 'convert')->first();

        $event = Event::create([
            'infrastructure_event_type_id' => $type->id,
            'control_id'                   => $database->id,
            'records_processed'            => $quantity
        ]);

        if (! empty($extra)) {
            EventMetadata::create([
                'infrastructure_event_id' => $event->id,
                'data'                    => $extra
            ]);
        }
    }
}
