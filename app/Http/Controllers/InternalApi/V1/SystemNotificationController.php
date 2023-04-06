<?php

namespace App\Http\Controllers\InternalApi\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NotificationChannelSubscriptionSetting;
use App\Models\NotificationChannelSubscription;
use App\Models\NotificationChannel;
use App\Models\NotificationType;
use Auth;
use App\Classes\Notifications\Exceptions\GenericNotificationException;

class SystemNotificationController extends Controller
{
    public function list()
    {
        $notifications = NotificationChannelSubscription::with(['channel', 'type', 'type.settings', 'settings', 'mostRecentHistory'])->get();

        $notifications->map(function ($notification) {
            $notification->descriptor = NULL;

            if (! empty($notification->type->descriptor_setting)) {
                $descriptor_setting_id = $notification->type->settings->where('key', $notification->type->descriptor_setting)->first()?->id;

                $notification->descriptor = $notification->settings->where('setting_id', $descriptor_setting_id)->first()?->value;
            }

            return $notification;
        });

        return response()->success(
            $notifications
        );
    }

    public function types()
    {
        return response()->success(
            NotificationType::with('settings')->get()
        );
    }

    public function channels()
    {
        return response()->success(
            NotificationChannel::all()
        );
    }

    public function store(Request $request)
    {
        $channel = NotificationChannel::find($request->channel_id);

        if (! $channel) {
            return response()->error('Invalid channel provided.', [], '404');
        }

        $type = NotificationType::with('settings')->find($request->type_id);
        
        if (! $type) {
            return response()->error('Invalid notification type provided.', [], '404');
        }

        $input = $request->settings;

        $errors = $this->validateInput($input, $type->settings);

        if (! empty($errors)) {
            return response()->error('Please correct your input.', compact('errors'), 500);
        }

        try {
            $this->validateWithNotification($input, $type);
        } catch (GenericNotificationException $e) {
            return response()->error($e->getMessage(), [], 500);
        } catch (Exception $e) {
            return response()->error('An unexpected error occurred.', ['message' => $e->getMessage()], 500);
        }

        $subscription = NotificationChannelSubscription::create([
            'channel_id' => $request->channel_id,
            'type_id'    => $request->type_id,
            'user_id'    => Auth::user()->id
        ]);

        foreach ($type->settings as $setting) {
            NotificationChannelSubscriptionSetting::create([
                'subscription_id' => $subscription->id,
                'setting_id'      => $setting->id,
                'value'           => $input[$setting->key] ?? NULL,
                'user_id'         => Auth::user()->id
            ]);
        }

        return response()->success([], 'Subscription successfully added.');
    }

    public function update(Request $request, NotificationChannelSubscription $subscription)
    {
        $channel = NotificationChannel::find($request->channel_id);

        if (! $channel) {
            return response()->error('Invalid channel provided.', [], '404');
        }

        $type = NotificationType::with('settings')->find($request->type_id);
        
        if (! $type) {
            return response()->error('Invalid notification type provided.', [], '404');
        }

        $input = $request->settings;

        $errors = $this->validateInput($input, $type->settings);

        if (! empty($errors)) {
            return response()->error('Please correct your input.', compact('errors'), 500);
        }

        try {
            $this->validateWithNotification($input, $type);
        } catch (GenericNotificationException $e) {
            return response()->error($e->getMessage(), [], 500);
        } catch (Exception $e) {
            return response()->error('An unexpected error occurred.', ['message' => $e->getMessage()], 500);
        }

        foreach ($type->settings as $setting) {
            NotificationChannelSubscriptionSetting::updateOrCreate([
                    'subscription_id' => $subscription->id,
                    'setting_id'      => $setting->id,
                ], [
                    'value'   => $input[$setting->key] ?? NULL,
                    'user_id' => Auth::user()->id
                ]);
        }

        return response()->success([], 'Subscription successfully updated.');
    }

    public function destroy(NotificationChannelSubscription $subscription)
    {
        $subscription->delete();

        return response()->success([], 'Subscription successfully deleted.');
    }

    public function show(NotificationChannelSubscription $subscription)
    {
        $subscription->load('settings', 'settings.setting');

        $key_value_settings = $subscription->settings->mapWithKeys(function ($setting) {
            return [$setting->setting->key => $setting->value];
        });

        return response()->success(array_merge($subscription->toArray(), ['settings' => $key_value_settings]));
    }

    /**
     * Validate our input against the settings (is_required) and their validation rules (input_validation)
     *
     * @param  array $input    The input to validate
     * @param  array $settings Our notification settings
     * @return array An array of errors, [] if no errors
     */
    private function validateInput(array $input, \Illuminate\Database\Eloquent\Collection $settings): array
    {
        $errors = [];

        foreach ($settings as $setting) {
            $value = trim($input[$setting->key] ?? NULL);
            $input[$setting->key] = $value;

            if ($setting->is_required && empty($value)) {
                $errors[] = "{$setting->key} is a required_field.";
                continue;
            }

            if (! empty($value) && ! empty($setting->input_validation)) {
                if (! preg_match('/' . $setting->input_validation . '/', $value)) {
                    $errors[] = "{$setting->key} is not a valid value.";
                }
            }
        }

        return $errors;
    }

    /**
     * Validate our settings with the underlying notification
     *
     * @param  array                        $settings_input The settings to validate
     * @param  array                        $type           The notification type
     * @throws GenericNotificationException If validation fails (use $e->getMessage()) to get underlying message
     */
    private function validateWithNotification(array $settings_input, NotificationType $type): void
    {
        $notification_data = NotificationChannelSubscription::buildNotificationData('testing', ['what_is_this' => 'A testing notification piece of data.']);

        $class = "\\App\\Classes\\Notifications\\{$type['class']}";

        $notification = new $class($settings_input, $notification_data);

        if (! $notification->validate()) {
            throw new GenericNotificationException("Notification validation failed.");
        }
    }
}
