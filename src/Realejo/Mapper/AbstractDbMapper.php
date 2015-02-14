<?php
namespace Realejo\Mapper;

use Zend\Db\TableGateway\Feature\GlobalAdapterFeature;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\TableGateway;
use Zend\Stdlib\Hydrator\ClassMethods;
use Zend\Stdlib\Hydrator\HydratorInterface;
use Zend\Paginator\Paginator;
use Zend\Paginator\Adapter\DbSelect as PaginatorDbAdapter;

use Realejo\Cache;
use Realejo\Mapper\PaginatorConfig;

class AbstractDbMapper
{

    /**
     * Define se deve usar o cache ou não
     *
     * @var boolean
     */
    protected $useCache = false;

    /**
     * Define de deve usar o paginator
     *
     * @var boolean
     */
    private $usePaginator = false;

    /**
     * Define a tabela a ser usada
     *
     * @var string
     */
    protected $table;

    /**
     * Define o nome da chave
     *
     * @var string
     */
    protected $key;

    /**
     * Define a ordem padrão a ser usada na consultas
     *
     * @var string
     */
    protected $order;

    /**
     * Define se deve remover os registros ou apenas marcar como removido
     *
     * @var boolean
     */
    protected $useDeleted = false;

    /**
     * Define se deve mostrar os registros marcados como removido
     *
     * @var boolean
     */
    protected $showDeleted = false;

    /**
     *
     * @var array
     */
    private $_lastInsertSet;

    /**
     *
     * @var int|array
     */
    private $_lastInsertKey;

    /**
     *
     * @var array
     */
    private $_lastUpdateSet;

    /**
     *
     * @var array
     */
    private $_lastUpdateDiff;

    /**
     *
     * @var int|array
     */
    private $_lastUpdateKey;

    /**
     *
     * @var int|array
     */
    private $_lastDeleteKey;

    /**
     *
     * @var Zend\Db\TableGateway\TableGateway
     */
    private $_tableGateway;

    /**
     *
     * @var Zend\Db\TableGateway\AdapterInterface
     */
    private $_dbAdapter;

    /**
     * Não pode ser usado dentro do Loader pois cada classe tem configurações diferentes
     *
     * @var \Zend\Cache\Storage\Adapter\Filesystem
     */
    private $_cache;

    /**
     * Não pode ser usado dentro do Loader pois cada classe tem configurações diferentes
     *
     * @var \Realejo\Mapper\PaginatorConfig
     */
    private $_paginatorConfig;

    /**
     *
     * @return TableGateway
     */
    public function getTableGateway()
    {
        // Verifica se já está carregado
        if (isset($this->_tableGateway)) {
            return $this->_tableGateway;
        }

        if (empty($this->table)) {
            throw new \Exception('Tabela não definida em ' . get_class($this) . '::getTable()');
        }

        // Define o adapter padrão
        if (empty($this->_dbAdapter)) {
            $this->_dbAdapter = GlobalAdapterFeature::getStaticAdapter();
        }

        // Verifica se tem adapter válido
        if (! ($this->_dbAdapter instanceof Adapter)) {
            throw new \Exception("Adapter dever ser uma instancia de Adapter");
        }
        $this->_tableGateway = new TableGateway($this->table, $this->_dbAdapter);

        // retorna o tabela
        return $this->_tableGateway;
    }

    /**
     * Return the where clause
     *
     * @param string|array $where
     *            OPTIONAL Consulta SQL
     *
     * @return array null
     */
    public function getWhere($where = null)
    {
        // Sets where is array
        $this->where = array();

        // Checks $where is not null
        if (empty($where)) {

            // Checks if $where should use deleted
            if ($this->getUseDeleted() && ! $this->getShowDeleted()) {
                $this->where[] = "{$this->getTableGateway()->getTable()}.deleted=0";
            }

            // return the $where so far
            return $this->where;
        }

        // Checks $where is not array
        if (! is_array($where)) {
            $where = array($where);
        }

        // Checks if $where should use deleted
        if ($this->getUseDeleted() && ! $this->getShowDeleted() && !isset($where['deleted'])) {
            $where['deleted'] = 0;
        }

        foreach ($where as $id => $w) {

            // Checks $where is not string
            // @todo deveria ser Expression ou PredicateInterface
            if ($w instanceof \Zend\Db\Sql\Expression || $w instanceof \Zend\Db\Sql\Predicate\PredicateInterface) {
                $this->where[] = $w;

            // Checks is deleted
            } elseif ($id === 'deleted' && $w === false) {
                $this->where[] = "{$this->getTableGateway()->getTable()}.deleted=0";
            } elseif ($id === 'deleted' && $w === true) {
                $this->where[] = "{$this->getTableGateway()->getTable()}.deleted=1";

            // Checks $id is not numeric and $w is numeric
            } elseif (!is_numeric($id) && is_numeric($w)) {
                if (strpos($id, '.') === false) {
                    $id = $this->getTableGateway()->getTable() . ".$id";
                }
                $this->where[] = "$id=$w";

            // Checks $id is not numeric and $w is string
            } elseif (!is_numeric($id) && is_string($id)) {
                if (strpos($id, '.') === false) {
                    $id = $this->getTableGateway()->getTable() . ".$id";
                }
                $this->where[] = "$id='$w'";

            // Return $id is not numeric and $w is string
            } else {
                throw new \Exception('Condição inválida em ' . get_class($this) . '::getWhere()');
            }
        }

        return $this->where;
    }

    /**
     * Retorna o select para a consulta
     *
     * @param mixed $where
     *            OPTIONAL Condições SQL
     * @param array|int $order
     *            OPTIONAL Ordem dos registros
     * @param int $count
     *            OPTIONAL Limite de registros
     * @param int $offset
     *            OPTIONAL Offset
     *
     * @return \Zend\Db\Sql\Select
     */
    public function getSelect($where = null, $order = null, $count = null, $offset = null)
    {
        $select = $this->getSQLSelect();

        // Define a ordem
        if (empty($order)) {
            $order = $this->getOrder();
        }
        if (! empty($order)) {
            $select->order($order);
        }

        // Verifica se há paginação
        if (! is_null($count)) {
            $select->limit($count);
        }

        // Verifica se há paginação
        if (! is_null($offset)) {
            $select->offset($offset);
        }

        // Define o where
        $where = $this->getWhere($where);
        if (! empty($where)) {
            $select->where($where);
        }

        return $select;
    }

    /**
     * Retorna o Select básico do model
     * Sobrescreva este método para inlcuir os joins
     *
     * @return \Zend\Db\Sql\Select
     */
    public function getSQLSelect()
    {
        return $this->getTableGateway()
                    ->getSql()
                    ->select();
    }

    /**
     * Retorna a consulta SQL que será executada
     *
     * @param mixed $where
     *            OPTIONAL Condições SQL
     * @param array|int $order
     *            OPTIONAL Ordem dos registros
     * @param int $count
     *            OPTIONAL Limite de registros
     * @param int $offset
     *            OPTIONAL Offset
     *
     * @return string
     */
    public function getSQlString($where = null, $order = null, $count = null, $offset = null)
    {
        return $this->getSelect($where, $order, $count, $offset)
                    ->getSqlString($this->getTableGateway()
                                        ->getAdapter()
                                        ->getPlatform());
    }

    /**
     * Retorna vários registros da tabela
     *
     * @param mixed $where
     *            OPTIONAL Condições SQL
     * @param array|int $order
     *            OPTIONAL Ordem dos registros
     * @param int $count
     *            OPTIONAL Limite de registros
     * @param int $offset
     *            OPTIONAL Offset
     *
     * @return array
     */
    public function fetchAll($where = null, $order = null, $count = null, $offset = null)
    {
        // Cria a assinatura da consulta
        if ($where instanceof \Zend\Db\Sql\Select) {
            $md5 = md5($where->getSqlString());
        } else {
            $md5 = md5(var_export($where, true) . var_export($order, true) . var_export($count, true) . var_export($offset, true) . var_export($this->getShowDeleted(), true) . var_export($this->getUseDeleted(), true));
        }

        // Verifica se tem no cache
        // Se estiver usando o paginador, o cache é controlado pelo Zend\Paginator
        if ($this->getUseCache() && !$this->getUsePaginator() && $this->getCache()->hasItem($md5)) {
            return $this->getCache()->getItem($md5);
        }

        $select = $this->getSelect($where, $order, $count, $offset);

        // Verifica se deve usar o Paginator
        if ($this->getUsePaginator()) {

            // Configura o fetchAll com o paginator
            $fetchAll = new Paginator(new PaginatorDbAdapter(
                $select,
                $this->getTableGateway()->getAdapter()
            ));

            // Verifica se deve usar o cache
            if ($this->getUseCache()) {
                $fetchAll->setCacheEnabled(true)->setCache($this->getCache());
            }

            // Configura o paginator
            $fetchAll->setPageRange($this->getPaginatorConfig()->getPageRange());
            $fetchAll->setCurrentPageNumber($this->getPaginatorConfig()->getCurrentPageNumber());
            $fetchAll->setItemCountPerPage($this->getPaginatorConfig()->getItemCountPerPage());

            // retorna o resultado da consulta
            return $fetchAll;
        }


        // Recupera os registros do banco de dados
        $fetchAll = $this->getTableGateway()->selectWith($select);

        // Verifica se foi localizado algum registro
        if (! is_null($fetchAll) && count($fetchAll) > 0) {
            // Passa o $fetch para array para poder incluir campos extras
            $fetchAll = $fetchAll->toArray();

            // Verifica se deve adicionar campos extras
            $fetchAll = $this->getFetchAllExtraFields($fetchAll);
        } else {
            $fetchAll = null;
        }

        // Grava a consulta no cache
        if ($this->getUseCache()) {
            $this->getCache()->setItem($md5, $fetchAll);
        }

        // retorna o resultado da consulta
        return $fetchAll;
    }

    /**
     * Recupera um registro
     *
     * @param mixed $where
     *            Condições para localizar o usuário
     * @param array|string $order
     *            OPTIONAL Ordem a ser considerada
     *
     * @return array|null array com os dados do usuário ou null se não localizar
     */
    public function fetchRow($where, $order = null)
    {
        // Define o código do usuário
        if (is_numeric($where)) {
            $where = array(
                $this->key => $where
            );
        }

        // Recupera o usuário
        $row = $this->fetchAll($where, $order, 1);

        // Retorna o usuário
        return (! is_null($row) && count($row) > 0) ? $row[0] : null;
    }

    /**
     * Retorna um array associado com os usuários com a chave sendo o código deles
     *
     * @param mixed $where
     *            OPTIONAL Condições SQL
     * @param array|int $order
     *            OPTIONAL Ordem dos registros
     * @param int $count
     *            OPTIONAL Limite de registros
     * @param int $offset
     *            OPTIONAL Offset
     *
     * @return array
     */
    public function fetchAssoc($where = null, $order = null, $count = null, $offset = null)
    {
        $rowset = $this->fetchAll($where, $order, $count, $offset);
        if (empty($rowset)) {
            return null;
        }
        $return = array();
        foreach ($rowset as $row) {
            $return[$row[$this->key]] = $row;
        }

        return $return;
    }

    /**
     * Retorna o total de registros encontrados com a consulta
     *
     * @todo se usar consulta com mais de uma tabela talvez de erro
     *
     * @param string|array $where
     *            An SQL WHERE clause
     *
     * @return int
     */
    public function fetchCount($where = null)
    {
        // Define o select
        $select = $this->getSelect($where);

        // Altera as colunas
        $select->reset('columns')->columns(new \Zend\Db\Sql\Expression('count(*) as total'));

        $fetchRow = $this->fetchRow($select);

        if (empty($fetchRow)) {
            return 0;
        }

        return $fetchRow['total'];
    }

    /**
     * Inclui campos extras ao retorno do fetchAll quando não estiver usando a paginação
     *
     * @param array $fetchAll
     *
     * @return array
     */
    protected function getFetchAllExtraFields($fetchAll)
    {
        return $fetchAll;
    }

    /**
     * Grava um novo registro
     *
     * @param array $dados
     *            Dados a serem cadastrados
     *
     * @return int boolean
     */
    public function insert($set)
    {

        // Verifica se há algo a ser adicionado
        if (empty($set)) {
            return false;
        }

        // Remove os campos vazios
        foreach ($set as $field => $value) {
            if (is_string($value)) {
                $set[$field] = trim($value);
                if ($set[$field] === '') {
                    $set[$field] = null;
                }
            }
        }

        // Grava o ultimo set incluído para referencia
        $this->_lastInsertSet = $set;

        // Grava o set no BD
        $this->getTableGateway()->insert($set);

        // Recupera a chave gerada do registro
        if (is_array($this->key)) {
            $key = array();
            foreach ($this->key as $k) {
                if (isset($set[$k])) {
                    $key[$k] = $set[$k];
                } else {
                    $key = false;
                    break;
                }
            }
        } elseif (isset($set[$this->key])) {
            $key = $set[$this->key];
        }

        if (empty($key)) {
            $key = $this->getTableGateway()
                ->getAdapter()
                ->getDriver()
                ->getLastGeneratedValue();
        }

        // Grava a chave criada para referencia
        $this->_lastInsertKey = $key;

        // Limpa o cache se necessário
        if ($this->getUseCache()) {
            $this->getCache()->clean();
        }

        // Retorna o código do registro recem criado
        return $key;
    }

    /**
     * Altera um registro
     *
     * @param array $set
     *            Dados a serem atualizados
     * @param int $key
     *            Chave do registro a ser alterado
     *
     * @return boolean
     */
    public function update($set, $key)
    {
        // Verifica se o código é válido
        if (! is_numeric($key)) {
            throw new \Exception("O código <b>'$key'</b> inválido em " . get_class($this) . "::update()");
        }

        // Verifica se há algo para alterar
        if (empty($set)) {
            return false;
        }

        // Recupera os dados existentes
        $row = $this->fetchRow($key);

        // Verifica se existe o registro
        if (empty($row)) {
            return false;
        }

        // Remove os campos vazios
        foreach ($set as $field => $value) {
            if (is_string($value)) {
                $set[$field] = trim($value);
                if ($set[$field] === '') {
                    $set[$field] = null;
                }
            }
        }

        // Verifica se há o que atualizar
        $diff = array_diff_assoc($set, $row);

        // Grava os dados alterados para referencia
        $this->_lastUpdateSet = $set;
        $this->_lastUpdateKey = $key;

        // Grava o que foi alterado
        $this->_lastUpdateDiff = array();
        foreach ($diff as $field => $value) {
            $this->_lastUpdateDiff[$field] = array(
                $row[$field],
                $value
            );
        }

        // Verifica se há algo para atualizar
        if (empty($diff)) {
            return false;
        }

        // Define a chave a ser usada
        $key = array(
            $this->key => $key
        );

        // Salva os dados alterados
        $return = $this->getTableGateway()->update($diff, $key);

        // Limpa o cache, se necessário
        if ($this->getUseCache()) {
            $this->getCache()->clean();
        }

        // Retorna que o registro foi alterado
        return $return;
    }

    /**
     * Excluí um registro
     *
     * @param int $cda
     *            Código da registro a ser excluído
     *
     * @return bool Informa se teve o regsitro foi removido
     */
    public function delete($key)
    {
        if (! is_numeric($key) || empty($key)) {
            throw new \Exception("O código <b>'$key'</b> inválido em " . get_class($this) . "::delete()");
        }

        // Grava os dados alterados para referencia
        $this->_lastDeleteKey = $key;

        // Define a chave a ser usada
        $key = array(
            $this->key => $key
        );

        // Verifica se deve marcar como removido ou remover o registro
        if ($this->useDeleted === true) {
            $return = $this->getTableGateway()->update(array(
                'deleted' => 1
            ), $key);
        } else {
            $return = $this->getTableGateway()->delete($key);
        }

        // Limpa o cache se necessario
        if ($this->getUseCache()) {
            $this->getCache()->clean();
        }

        // Retorna se o registro foi excluído
        return $return;
    }

    /**
     * Retorna o frontend para gravar o cache
     *
     * @return \Zend\Cache\Storage\Adapter\Filesystem
     */
    public function getCache()
    {
        if (! isset($this->_cache)) {
            $this->_cache = new Cache();
        }
        return $this->_cache->getFrontend(get_class($this));
    }

    /**
     * Define se deve usar o cache
     *
     * @param boolean $useCache
     *
     * @return \Realejo\Mapper\AbstractDbMapper
     */
    public function setUseCache($useCache)
    {
        // Grava o cache
        $this->useCache = $useCache;

        // Mantem a cadeia
        return $this;
    }

    /**
     * PAGINATOR
     * Diferente do cache, se gravar qualquer variável do paginator ele será criado
     */

    /**
     * Define a configuração do paginator a ser usado no mapper
     *
     * @return \Realejo\Mapper\AbstractDbMapper
     */
    public function setPaginatorConfig($paginatorConfig)
    {
        if (! $paginatorConfig instanceof PaginatorConfig) {
            throw new \Exception('Configuração do paginator deve ser do tipo PaginatorConfig');
        }

        $this->_paginatorConfig = $paginatorConfig;

        $this->usePaginator = true;

        return $this;
    }

    /**
     * Retorna a configuração do paginator a ser usado
     *
     * @return \Realejo\Mapper\PaginatorConfig
     */
    public function getPaginatorConfig()
    {
        if (! isset($this->_paginatorConfig)) {
            $this->_paginatorConfig = new \Realejo\Mapper\PaginatorConfig();
        }

        $this->usePaginator = true;

        return $this->_paginatorConfig;
    }

    /**
     * Define se deve usar o paginator
     *
     * @param boolean $usepaginator
     */
    public function setUsePaginator($usePaginator)
    {
        // Grava o paginator
        $this->usePaginator = $usePaginator;

        // Mantem a cadeia
        return $this;
    }

    /**
     * Retorna se deve usar o paginator
     *
     * @return boolean
     */
    public function getUsePaginator()
    {
        return $this->usePaginator;
    }

    /**
     * Getters and setters
     */

    /**
     *
     * @param string $key
     *
     * @return \Realejo\Mapper\AbstractDbMapper
     */
    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     *
     * @param string $key
     *
     * @return \Realejo\Mapper\AbstractDbMapper
     */
    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     *
     * @param string $order
     *
     * @return \Realejo\Mapper\AbstractDbMapper
     */
    public function setOrder($order)
    {
        $this->order = $order;
        return $this;
    }

    /**
     *
     * @return string|array
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Retorna se deve usar o cache
     *
     * @return boolean
     */
    public function getUseCache()
    {
        return $this->useCache;
    }

    /**
     * Retorna se irá usar o campo deleted ou remover o registro quando usar delete()
     *
     * @return boolean
     */
    public function getUseDeleted()
    {
        return $this->useDeleted;
    }

    /**
     * Define se irá usar o campo deleted ou remover o registro quando usar delete()
     *
     * @param boolean $useDeleted
     *
     * @return \Realejo\Mapper\AbstractDbMapper
     */
    public function setUseDeleted($useDeleted)
    {
        $this->useDeleted = $useDeleted;

        // Mantem a cadeia
        return $this;
    }

    /**
     * Retorna se deve retornar os registros marcados como removidos
     *
     * @return boolean
     */
    public function getShowDeleted()
    {
        return $this->showDeleted;
    }

    /**
     * Define se deve retornar os registros marcados como removidos
     *
     * @param boolean $showDeleted
     *
     * @return \Realejo\Mapper\AbstractDbMapper
     */
    public function setShowDeleted($showDeleted)
    {
        $this->showDeleted = $showDeleted;

        // Mantem a cadeia
        return $this;
    }

    /**
     *
     * @return array
     */
    public function getLastInsertSet()
    {
        return $this->_lastInsertSet;
    }

    /**
     *
     * @return int
     */
    public function getLastInsertKey()
    {
        return $this->_lastInsertKey;
    }

    /**
     *
     * @return array
     */
    public function getLastUpdateSet()
    {
        return $this->_lastUpdateSet;
    }

    /**
     *
     * @return array
     */
    public function getLastUpdateDiff()
    {
        return $this->_lastUpdateDiff;
    }

    /**
     *
     * @return int
     */
    public function getLastUpdateKey()
    {
        return $this->_lastUpdateKey;
    }

    /**
     *
     * @return int
     */
    public function getLastDeleteKey()
    {
        return $this->_lastDeleteKey;
    }
}
