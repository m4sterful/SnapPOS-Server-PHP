<?php

namespace App\Support\Setup;

use RuntimeException;

class EnvironmentFileManager
{
    public function path(): string
    {
        return base_path('.env');
    }

    public function examplePath(): string
    {
        return base_path('.env.example');
    }

    public function exists(): bool
    {
        return is_file($this->path());
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public function write(array $values): void
    {
        $path = $this->path();
        $content = $this->exists()
            ? (string) file_get_contents($path)
            : $this->defaultTemplate();

        foreach ($values as $key => $value) {
            $content = $this->setValue($content, $key, $value ?? '');
        }

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('Unable to write the application .env file.');
        }
    }

    protected function defaultTemplate(): string
    {
        if (is_file($this->examplePath())) {
            return (string) file_get_contents($this->examplePath());
        }

        throw new RuntimeException('Unable to create .env because .env.example is missing.');
    }

    protected function setValue(string $content, string $key, string $value): string
    {
        $escaped = $this->escape($value);
        $pattern = "/^{$key}=.*$/m";

        if (preg_match($pattern, $content) === 1) {
            return (string) preg_replace($pattern, "{$key}={$escaped}", $content);
        }

        return rtrim($content).PHP_EOL."{$key}={$escaped}".PHP_EOL;
    }

    protected function escape(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/\s|#|=|"/', $value) === 1) {
            return '"'.addcslashes($value, "\\\"").'"';
        }

        return $value;
    }
}
