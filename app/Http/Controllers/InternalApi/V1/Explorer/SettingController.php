<?php

namespace App\Http\Controllers\InternalApi\V1\Explorer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Explorer\Project;
use App\Models\Explorer\ProjectSetting;
use App\Models\Explorer\ProjectSettingValue;
use App\Attributes\Can;
use DB;

class SettingController extends Controller
{
    public function list(Project $project)
    {
        $settings = ProjectSetting::select(
            'bp_project_settings.id as setting_id',
            'bp_project_setting_values.id as value_id',
            'bp_project_settings.name',
            'bp_project_settings.label',
            'bp_project_settings.type',
            'bp_project_setting_values.value'
        ) 
            ->leftJoin('bp_project_setting_values', function ($join) use ($project) {
                $join->on('bp_project_settings.id', '=', 'bp_project_setting_values.setting_id')
                    ->on('project_id', '=', DB::raw($project->id));
            })
            ->where('bp_project_setting_values.is_deleted')
            ->get();
        
        return response()->success($settings);
    }

    #[Can(permission: 'project_manage', product: 'studio', id: 'project.id')]
    public function update(Request $request, Project $project)
    {
        $request->validateWithErrors([
            'settings' => 'required'
        ]);

        foreach ($request->settings as $setting) {
            ProjectSettingValue::where('id', $setting->value_id)
                ->where('setting_id', $setting->setting_id)
                ->update(['value' => $setting->value]);
        }

        return response()->success();
    }
}
