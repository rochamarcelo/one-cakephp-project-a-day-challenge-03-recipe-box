<?php


namespace App\Model;


use Cake\Core\Exception\CakeException;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Locator\AbstractLocator;
use Cake\ORM\AssociationCollection;
use Cake\Utility\Inflector;

class DocumentLocator extends AbstractLocator
{

    /**
     * @inheritDoc
     */
    protected function createInstance(string $alias, array $options)
    {
        if (strpos($alias, '\\') === false) {
            [, $classAlias] = pluginSplit($alias);
            $options = ['alias' => $classAlias] + $options;
        } elseif (!isset($options['alias'])) {
            $options['className'] = $alias;
            /** @psalm-suppress PossiblyFalseOperand */
            $alias = substr($alias, strrpos($alias, '\\') + 1, -5);
        }

        if (isset($this->_config[$alias])) {
            $options += $this->_config[$alias];
        }

        $allowFallbackClass = $options['allowFallbackClass'] ?? $this->allowFallbackClass;
        $className = $this->_getClassName($alias, $options);

        debug(compact("className"));
        exit;
        if ($className) {
            $options['className'] = $className;
        } elseif ($allowFallbackClass) {
            if (empty($options['className'])) {
                $options['className'] = $alias;
            }
            if (!isset($options['table']) && strpos($options['className'], '\\') === false) {
                [, $table] = pluginSplit($options['className']);
                $options['table'] = Inflector::underscore($table);
            }
            $options['className'] = $this->fallbackClassName;
        } else {
            $message = $options['className'] ?? $alias;
            $message = '`' . $message . '`';
            if (strpos($message, '\\') === false) {
                $message = 'for alias ' . $message;
            }
            throw new MissingTableClassException([$message]);
        }

        if (empty($options['connection'])) {
            if (!empty($options['connectionName'])) {
                $connectionName = $options['connectionName'];
            } else {
                /** @var \Cake\ORM\Table $className */
                $className = $options['className'];
                $connectionName = $className::defaultConnectionName();
            }
            $options['connection'] = ConnectionManager::get($connectionName);
        }
        if (empty($options['associations'])) {
            $associations = new AssociationCollection($this);
            $options['associations'] = $associations;
        }

        $options['registryAlias'] = $alias;
        $instance = $this->_create($options);

        if ($options['className'] === $this->fallbackClassName) {
            $this->_fallbacked[$alias] = $instance;
        }

        return $instance;
    }
}
