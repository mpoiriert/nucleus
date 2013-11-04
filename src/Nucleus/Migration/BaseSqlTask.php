<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\Migration;

use RuntimeException;

/**
 *
 * @author mcayer
 */
abstract class BaseSqlTask extends BaseMigrationTask
{
    /**
     * 
     * @param array $configuration
     * @throws RuntimeException
     * 
     * @\Nucleus\IService\DependencyInjection\Inject(configuration="$")
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;

        if (!isset($this->configuration['basePath'])) {
            throw new RuntimeException("No base path specified for SqlTask");
        }
    }

    /**
     * 
     * @param array $parameters
     * @throws RuntimeException
     */
    public function prepare(array $parameters)
    {
        parent::prepare($parameters);

        if (!isset($this->parameters['filename'])) {
            throw new RuntimeException("No filename specified for SqlTask");
        }

        if (!file_exists($this->getFile())) {
            throw new RuntimeException("Filename [" . $this->parameters['filename'] . "] specified for SqlTask doesn't exists in [" . $this->configuration['basePath'] . "]");
        }
    }

    /**
     * 
     * @return string Path to SQL file
     */
    protected function getFile()
    {
        return $this->configuration['basePath'] . $this->parameters['filename'];
    }
}
