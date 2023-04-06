### Bytespree Configurations

After moving to Laravel, Bytespree's code will never call `getenv()`. Instead, it will set up `config()` values on boot via Laravel's configuration loader.

### Configratuion Values

A current list of ENV => config() values is seen below:

| ENV Variable | Config Accessor | Note        |
| ------------ | --------------- | ----------- |
| APPLICATION_PATH | `config('app.path')` | |
| ATTACH_DIRECTORY            | `config('app.attach_directory')` | |
| CI_ENV      | `config('app.env')` | use `app()->isProduciont()` or `app()->isLocal()`
| DEV_LOG_FILE_PROVIDER       | `config('logging.file.provider')` |
| DEV_LOG_FILE_USE_COLOR      | `config('logging.file.use_color')` |
| DEV_LOG_OUTPUT              | `config('logging.output')` |
| DI_DATABASE | `config('database.connections.pgsql.database')` |
| DI_DEV_EMAIL | `config('mail.dev.email')` |
| DI_HOSTNAME | `config('database.connections.pgsql.host')` |
| DI_PASSWORD | `config('database.connections.pgsql.password')` |
| DI_PORT | `config('database.connections.pgsql.port')` |
| DI_USERNAME | `config('database.connections.pgsql.username')` |
| DIGITAL_OCEAN_PG_VERSION    | `config('services.digital_ocean.pg_version')` |
| DIGITAL_OCEAN_SPACES_KEY    | `config('services.digital_ocean.spaces.key')` |
| DIGITAL_OCEAN_SPACES_SECRET | `config('services.digital_ocean.spaces.secret')` |
| DIGITAL_OCEAN_TOKEN | `config('services.digital_ocean.token')` |
| DMIUX_URL                   | `config('services.dmiux.url')` |
| ENABLE_DB_CONNECTION_FIXES  | `config('database.connections.pgsql.connection_log')` |
| ENABLE_DB_CONNECTION_LOG    | `config('database.connections.pgsql.connection_log')` |
| ENCRYPT_DECRYPT_KEY | `config('app.encryt_decrypt_key')` |
| ENVIRONMENT_URL   | `config('app.url')` |
| FDW_FETCH_SIZE              | `config('database.connections.pgsql.fdw_fetch_size')` |
| FILE_UPLOAD_URL             | `config('services.file_upload.url')` |
| INTERCOM_SECRET             | `config('services.intercom.secret')` |
| MIN_PG_VERSION              | `config('database.connections.pgsql.min_version')` |
| ORCHESTRATION_API_KEY       | `config('orchestration.api_key')` |
| ORCHESTRATION_URL           | `config('orchestration.url')` |
| POSTMARK_API_KEY            | `config('services.postmark.api_key')` |
| PUSHER_APP_CLUSTER          | N/A, see `config('broadcasting.connections.pusher.options.host')` |
| PUSHER_APP_ID               | `config('broadcasting.connections.pusher.app_id')` |
| PUSHER_APP_KEY              | `config('broadcasting.connections.pusher.key')` |
| PUSHER_APP_SECRET           | `config('broadcasting.connections.pusher.secret')` |
| REGION_JENKINS_URL          | `config('services.jenkins.region_url')` |
| ROLLBAR_ACCESS_TOKEN        | `config('services.rollbar.access_token')` |
| SMTP_EMAIL | `config('mail.from.address')` |
| SMTP_PASSWORD | `config('mail.mailers.smtp.password')` |
| SMTP_PORT | `config('mail.mailers.smtp.port')` |
| SMTP_SERVER | `config('mail.mailers.smtp.host')` |
| SMTP_USERNAME | `config('mail.mailers.smtp.username')` |
| UPLOAD_DIRECTORY            | `config('app.upload_directory')` |
| ------------ | --------------- | ----------- |
| ------------ | --------------- | ----------- |
| ------------ | --------------- | ----------- |
| __UNKNOWNS__ |  |  |
| BP_ROLLBAR_ACCESS_TOKEN  | ... | used in `head.php` - why have ROLLBAR_ACCESS_TOKEN? |
| DATABASE_MIGRATIONS_ONLY | ... | used in `phpunit.yml` action |
| DIGITAL_OCEAN_DOMAIN     | ... | used in `Environment::getProviderDomain()` | 