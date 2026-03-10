<?php
declare(strict_types=1);

/**
 * Register a callable service entry for kr("module.action").
 */
function kr_register(string $name, callable $handler): void
{
    if (!isset($GLOBALS['kr_services']) || !is_array($GLOBALS['kr_services'])) {
        $GLOBALS['kr_services'] = [];
    }
    $GLOBALS['kr_services'][$name] = $handler;
}

/**
 * Call a previously registered service entry.
 */
function kr(string $name, mixed ...$args): mixed
{
    $services = $GLOBALS['kr_services'] ?? [];
    if (!isset($services[$name]) || !is_callable($services[$name])) {
        throw new RuntimeException("Service not found: {$name}");
    }
    return $services[$name](...$args);
}

/**
 * Get config value from global config loaded by core loader.
 */
function kr_config(?string $key = null, mixed $default = null): mixed
{
    $config = $GLOBALS['kr_config'] ?? [];
    if (!is_array($config)) {
        return $default;
    }
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? $default;
}

/**
 * Access the request context created by core loader.
 */
function kr_request(): KrRequestContext
{
    return $GLOBALS['kr_request'];
}

/**
 * Access the response context created by core loader.
 */
function kr_response(): KrResponseContext
{
    return $GLOBALS['kr_response'];
}

/**
 * Access the server context created by core loader.
 */
function kr_server(): KrServerContext
{
    return $GLOBALS['kr_server'];
}

/**
 * Access the member context created by core loader.
 */
function kr_member(): KrMemberContext
{
    return $GLOBALS['kr_member'];
}

/**
 * Access the storage context created by core loader.
 */
function kr_storage(): KrStorageContext
{
    return $GLOBALS['kr_storage'];
}
