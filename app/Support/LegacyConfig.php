<?php

namespace App\Support;

class LegacyConfig
{
    public static function configPath(): string
    {
        return base_path('config.php');
    }

    /**
     * @return array<string, mixed>
     */
    public static function load(): array
    {
        $path = static::configPath();

        if (! is_file($path)) {
            return static::defaults();
        }

        $config = include $path;

        if (! is_array($config)) {
            return static::defaults();
        }

        return array_merge(static::defaults(), $config);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function write(array $values): void
    {
        $config = array_merge(static::defaults(), $values);

        $export = [
            'db_name' => (string) $config['db_name'],
            'db_host' => (string) $config['db_host'],
            'db_port' => (string) $config['db_port'],
            'db_user' => (string) $config['db_user'],
            'db_password' => (string) $config['db_password'],
            'db_prefix' => (string) $config['db_prefix'],
            'seed_test_data' => (bool) $config['seed_test_data'],
        ];

        $content = <<<'CONFIG'
<?php

defined('DB_NAME') || define('DB_NAME', %s);
defined('DB_HOST') || define('DB_HOST', %s);
defined('DB_PORT') || define('DB_PORT', %s);
defined('DB_USER') || define('DB_USER', %s);
defined('DB_PASSWORD') || define('DB_PASSWORD', %s);
defined('DB_PREFIX') || define('DB_PREFIX', %s);
defined('DB_SEED_TEST_DATA') || define('DB_SEED_TEST_DATA', %s);

return [
    'db_name' => %s,
    'db_host' => %s,
    'db_port' => %s,
    'db_user' => %s,
    'db_password' => %s,
    'db_prefix' => %s,
    'seed_test_data' => %s,
];
CONFIG;

        $rendered = sprintf(
            $content,
            var_export($export['db_name'], true),
            var_export($export['db_host'], true),
            var_export($export['db_port'], true),
            var_export($export['db_user'], true),
            var_export($export['db_password'], true),
            var_export($export['db_prefix'], true),
            $export['seed_test_data'] ? 'true' : 'false',
            var_export($export['db_name'], true),
            var_export($export['db_host'], true),
            var_export($export['db_port'], true),
            var_export($export['db_user'], true),
            var_export($export['db_password'], true),
            var_export($export['db_prefix'], true),
            $export['seed_test_data'] ? 'true' : 'false',
        );

        file_put_contents(static::configPath(), $rendered.PHP_EOL);
    }

    public static function isConfigured(?array $config = null): bool
    {
        $config ??= static::load();

        return static::hasValue($config['db_name'])
            && static::hasValue($config['db_host'])
            && static::hasValue($config['db_port'])
            && static::hasValue($config['db_user']);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'db_name' => '',
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
            'db_user' => '',
            'db_password' => '',
            'db_prefix' => '',
            'seed_test_data' => false,
        ];
    }

    public static function hasValue(mixed $value): bool
    {
        return is_string($value) ? trim($value) !== '' : $value !== null;
    }
}
