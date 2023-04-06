<?php

namespace App\Classes\Notifications;

use Bytespree\Notifications\Exception\GenericNotificationException;

class Notification
{
    /**
     * @var array Options (data) to send with the notification
     */
    protected $options = [];

    /**
     * @var mixed Status code (response). NULL if not updated by underlying notification
     */
    protected $status_code = NULL;

    /**
     * @var mixed Status message (response). NULL if not updated by underlying notification
     */
    protected $status_message = NULL;

    public function __construct(array $settings = [], array $options = [])
    {
        $this->options = $options;
        $this->settings = $settings;

        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    /**
     * Process our underlying notification
     *
     * @return bool TRUE if successful, FALSE otherwise
     */
    public function process(): bool
    {
        try {
            $status = $this->send();

            return $status;
        } catch (GenericNotificationException $e) {
            $this->status_message = $e->getMessage();

            return FALSE;
        }
    }

    /**
     * Get a setting from our settings array
     *
     * @param  string $setting_key The key of the setting to get
     * @return mixed  The setting value, NULL if it doesn't exist
     */
    public function getSetting(string $setting_key)
    {
        return $this->settings[$setting_key] ?? NULL;
    }

    /**
     * Getter for status_code
     *
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->status_code;
    }

    /**
     * Getter for status_message
     *
     * @return mixed
     */
    public function getStatusMessage()
    {
        return $this->status_message;
    }
}
