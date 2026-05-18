<?php

namespace AppBundle\Settings;

use AppBundle\Audit\AuditLogManager;
use Doctrine\DBAL\Connection;

class SettingsManager
{
    private $connection;
    private $definitions;
    private $auditLogManager;
    private $loaded = false;
    private $settings = array();

    public function __construct(Connection $connection, SettingsDefinitionProvider $definitionProvider, AuditLogManager $auditLogManager)
    {
        $this->connection = $connection;
        $this->definitions = $definitionProvider->all();
        $this->auditLogManager = $auditLogManager;
    }

    public function get($name, $owner = null, $default = null)
    {
        $this->assertKnown($name);
        $this->load();

        return array_key_exists($name, $this->settings) && $this->settings[$name] !== null
            ? $this->settings[$name]
            : $default;
    }

    public function all($owner = null)
    {
        $this->load();

        $settings = array();
        foreach ($this->definitions as $name => $definition) {
            $settings[$name] = array_key_exists($name, $this->settings) ? $this->settings[$name] : null;
        }

        return $settings;
    }

    public function set($name, $value, $owner = null)
    {
        return $this->setMany(array($name => $value), $owner);
    }

    public function setMany(array $settings, $owner = null)
    {
        $this->load();
        $before = $this->settings;

        foreach ($settings as $name => $value) {
            $this->assertKnown($name);
            $this->persist($name, $value);
            $this->settings[$name] = $value;
        }

        $this->loaded = true;
        $this->auditLogManager->logSettings($before, $this->settings);

        return $this;
    }

    public function clear($name, $owner = null)
    {
        return $this->set($name, null, $owner);
    }

    private function load()
    {
        if ($this->loaded) {
            return;
        }

        $this->settings = array();
        $rows = $this->connection->fetchAll(
            'SELECT name, value FROM dmishh_settings WHERE owner_id IS NULL'
        );

        foreach ($rows as $row) {
            if (!isset($this->definitions[$row['name']])) {
                continue;
            }

            $this->settings[$row['name']] = $this->decode($row['value']);
        }

        $this->loaded = true;
    }

    private function persist($name, $value)
    {
        $storedValue = $this->encode($value);
        $exists = (bool) $this->connection->fetchColumn(
            'SELECT COUNT(*) FROM dmishh_settings WHERE name = :name AND owner_id IS NULL',
            array('name' => $name)
        );

        if ($exists) {
            $this->connection->executeUpdate(
                'UPDATE dmishh_settings SET value = :value WHERE name = :name AND owner_id IS NULL',
                array('value' => $storedValue, 'name' => $name)
            );

            return;
        }

        $this->connection->insert(
            'dmishh_settings',
            array(
                'name' => $name,
                'value' => $storedValue,
                'owner_id' => null,
            )
        );
    }

    private function encode($value)
    {
        return serialize($value);
    }

    private function decode($value)
    {
        if ($value === null) {
            return null;
        }

        $decoded = @unserialize($value);

        return $decoded === false && $value !== serialize(false) ? $value : $decoded;
    }

    private function assertKnown($name)
    {
        if (!isset($this->definitions[$name])) {
            throw new \InvalidArgumentException(sprintf('Unknown setting "%s".', $name));
        }
    }
}
