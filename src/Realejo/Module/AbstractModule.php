<?php
/**
 * Gerenciador do paginator utilizado pelo App_Model
 *
 * Ele é usado apenas para guarda a configuração da paginação. O paginator é
 * criado direto na consulta no retorno do fetchAll
 *
 * @link      http://github.com/realejo/libraray-zf2
 * @copyright Copyright (c) 2014 Realejo (http://realejo.com.br)
 * @license   http://unlicense.org
 */
namespace Realejo\Module;

abstract class AbstractModule
{
    abstract public function getDir();
    abstract public function getNamespace();

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                $this->getDir() . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    // if we're in a namespace deeper than one level we need to fix the \ in the path
                    $this->getNamespace() => $this->getDir() . '/src/' . str_replace('\\', '/' , $this->getNamespace()),
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include $this->getDir() . '/config/module.config.php';
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array()
        );
    }
}
