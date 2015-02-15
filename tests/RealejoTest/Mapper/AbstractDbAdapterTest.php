<?php
/**
 * TestDbAdapterTest test case.
 *
 * @link      http://github.com/realejo/libraray-zf2
 * @copyright Copyright (c) 2014 Realejo (http://realejo.com.br)
 * @license   http://unlicense.org
 */
namespace RealejoTest\Mapper;

use Zend\Db\Adapter\Adapter;
use RealejoTest\BaseTestCase;

class AbstractDbAdapterTest extends BaseTestCase
{
    /**
     * @var string
     */
    protected $tableName = "album";

    /**
     * @var string
     */
    protected $tableKeyName = "id";

    protected $tables = array('album');

    /**
     * @var TestDbAdapter
     */
    private $TestDbAdapter;

    /**
     * @var Zend\Db\Adapter\Adapter
     */
    protected $pdoAdapter = null;

    protected $defaultValues = array(
        array(
            'id' => 1,
            'artist' => 'Rush',
            'title' => 'Rush',
            'deleted' => 0
        ),
        array(
            'id' => 2,
            'artist' => 'Rush',
            'title' => 'Moving Pictures',
            'deleted' => 0
        ),
        array(
            'id' => 3,
            'artist' => 'Dream Theater',
            'title' => 'Images And Words',
            'deleted' => 0
        ),
        array(
            'id' => 4,
            'artist' => 'Claudia Leitte',
            'title' => 'Exttravasa',
            'deleted' => 1
        )
    );

    /**
     *
     * @return \Realejo\Db\TestDbAdapterTest
     */
    public function insertDefaultRows()
    {
        foreach ($this->defaultValues as $row) {
            $this->getAdapter()->query("INSERT into {$this->tableName}({$this->tableKeyName}, artist, title, deleted)
                VALUES ({$row[$this->tableKeyName]}, '{$row['artist']}', '{$row['title']}', {$row['deleted']});", Adapter::QUERY_MODE_EXECUTE);
        }
        return $this;
    }

    /**
     *
     * @return \Realejo\Db\TestDbAdapterTest
     */
    public function truncateTable()
    {
        $this->dropTables()->createTables();
        return $this;
    }


    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->dropTables()->createTables()->insertDefaultRows();

        $this->TestDbAdapter = new TestDbAdapter();
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->dropTables();

        unset($this->TestDbAdapter);
    }

    /**
     * test a criação com a conexão local de testes
     */
    public function testCreateTestDbAdapter()
    {
        $TestDbAdapter = new TestDbAdapter();
        $this->assertInstanceOf('Realejo\Mapper\AbstractDbMapper', $TestDbAdapter);
    }

    /**
     * teste o adapter
     */
    public function testAdatper()
    {
        $this->assertInstanceOf('\Zend\Db\Adapter\Adapter', $this->getAdapter());
    }

    /**
     * Tests TestDbAdapter->getOrder()
     */
    public function testOrder()
    {
        // Verifica a ordem padrão
        $this->assertNull($this->TestDbAdapter->getOrder());

        // Define uma nova ordem com string
        $this->TestDbAdapter->setOrder('id');
        $this->assertEquals('id', $this->TestDbAdapter->getOrder());

        // Define uma nova ordem com string
        $this->TestDbAdapter->setOrder('title');
        $this->assertEquals('title', $this->TestDbAdapter->getOrder());

        // Define uma nova ordem com array
        $this->TestDbAdapter->setOrder(array('id', 'title'));
        $this->assertEquals(array('id', 'title'), $this->TestDbAdapter->getOrder());
    }

    /**
     * Tests TestDbAdapter->getWhere()
     */
    public function testWhere()
    {

        // Marca pra usar o campo deleted
        $this->TestDbAdapter->setUseDeleted(true);

        // Verifica a ordem padrão
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->TestDbAdapter->getWhere());
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->TestDbAdapter->getWhere(null));
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->TestDbAdapter->getWhere(array()));
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->TestDbAdapter->getWhere(''));
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->TestDbAdapter->getWhere(0));

        $this->assertEquals(array("{$this->tableName}.deleted=1"), $this->TestDbAdapter->getWhere(array('deleted'=>true)));
        $this->assertEquals(array("{$this->tableName}.deleted=1"), $this->TestDbAdapter->getWhere(array('deleted'=>1)));
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->TestDbAdapter->getWhere(array('deleted'=>false)));
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->TestDbAdapter->getWhere(array('deleted'=>0)));

        $this->assertEquals(array(
            "outratabela.campo=0",
            "{$this->tableName}.deleted=0"
        ), $this->TestDbAdapter->getWhere(array('outratabela.campo'=>0)));

        $this->assertEquals(array(
                "outratabela.deleted=1",
                "{$this->tableName}.deleted=0"
        ), $this->TestDbAdapter->getWhere(array('outratabela.deleted'=>1)));

        $this->assertEquals(array(
                            "{$this->tableName}.{$this->tableKeyName}=1",
                            "{$this->tableName}.deleted=0"
        ), $this->TestDbAdapter->getWhere(array($this->tableKeyName=>1)));

        $dbExpression = new \Zend\Db\Sql\Expression('now()');
        $this->assertEquals(array(
            $dbExpression,
                "{$this->tableName}.deleted=0"
        ), $this->TestDbAdapter->getWhere(array($dbExpression)));

    }

    /**
     * Tests campo deleted
     */
    public function testDeletedField()
    {
        // Verifica se deve remover o registro
        $this->assertFalse($this->TestDbAdapter->getUseDeleted());
        $this->assertTrue($this->TestDbAdapter->setUseDeleted(true)->getUseDeleted());
        $this->assertFalse($this->TestDbAdapter->setUseDeleted(false)->getUseDeleted());
        $this->assertFalse($this->TestDbAdapter->getUseDeleted());

        // Verifica se deve mostrar o registro
        $this->assertFalse($this->TestDbAdapter->getShowDeleted());
        $this->assertFalse($this->TestDbAdapter->setShowDeleted(false)->getShowDeleted());
        $this->assertTrue($this->TestDbAdapter->setShowDeleted(true)->getShowDeleted());
        $this->assertTrue($this->TestDbAdapter->getShowDeleted());
    }

    /**
     * Tests TestDbAdapter->getSQlString()
     */
    public function testGetSQlString()
    {
        // Verfiica o padrão não usar o campo deleted e não mostrar os removidos
        $this->assertEquals('SELECT `album`.* FROM `album`', $this->TestDbAdapter->getSQlString(), 'showDeleted=false, useDeleted=false');

        // Marca para usar o campo deleted
        $this->TestDbAdapter->setUseDeleted(true);
        $this->assertEquals('SELECT `album`.* FROM `album` WHERE album.deleted=0', $this->TestDbAdapter->getSQlString(), 'showDeleted=false, useDeleted=true');

        // Marca para não usar o campo deleted
        $this->TestDbAdapter->setUseDeleted(false);

        $this->assertEquals('SELECT `album`.* FROM `album` WHERE album.id=1234', $this->TestDbAdapter->getSQlString(array('id'=>1234)));
        $this->assertEquals("SELECT `album`.* FROM `album` WHERE album.texto='textotextotexto'", $this->TestDbAdapter->getSQlString(array('texto'=>'textotextotexto')));

    }

    /**
     * Tests TestDbAdapter->testGetSQlSelect()
     */
    public function testGetSQlSelect()
    {
        $select = $this->TestDbAdapter->getSQlSelect();
        $this->assertInstanceOf('Zend\Db\Sql\Select', $select);
        $this->assertEquals($select->getSqlString($this->getAdapter()->getPlatform()), $this->TestDbAdapter->getSQlString());
    }

    /**
     * Tests TestDbAdapter->fetchAll()
     */
    public function testFetchAll()
    {

        // O padrão é não usar o campo deleted
        $albuns = $this->TestDbAdapter->fetchAll();
        $this->assertCount(4, $albuns, 'showDeleted=false, useDeleted=false');

        // Marca para mostrar os removidos e não usar o campo deleted
        $this->TestDbAdapter->setShowDeleted(true)->setUseDeleted(false);
        $this->assertCount(4, $this->TestDbAdapter->fetchAll(), 'showDeleted=true, useDeleted=false');

        // Marca pra não mostar os removidos e usar o campo deleted
        $this->TestDbAdapter->setShowDeleted(false)->setUseDeleted(true);
        $this->assertCount(3, $this->TestDbAdapter->fetchAll(), 'showDeleted=false, useDeleted=true');

        // Marca pra mostrar os removidos e usar o campo deleted
        $this->TestDbAdapter->setShowDeleted(true)->setUseDeleted(true);
        $albuns = $this->TestDbAdapter->fetchAll();
        $this->assertCount(4, $albuns, 'showDeleted=true, useDeleted=true');

        // Marca não mostrar os removios
        $this->TestDbAdapter->setShowDeleted(false);

        $albuns = $this->defaultValues;
        unset($albuns[3]); // remove o deleted=1
        $this->assertEquals($albuns, $this->TestDbAdapter->fetchAll());

        // Marca mostrar os removios
        $this->TestDbAdapter->setShowDeleted(true);

        $this->assertEquals($this->defaultValues, $this->TestDbAdapter->fetchAll());
        $this->assertCount(4, $this->TestDbAdapter->fetchAll());
        $this->TestDbAdapter->setShowDeleted(false);
        $this->assertCount(3, $this->TestDbAdapter->fetchAll());

        // Verifica o where
        $this->assertCount(2, $this->TestDbAdapter->fetchAll(array('artist'=>$albuns[0]['artist'])));
        $this->assertNull($this->TestDbAdapter->fetchAll(array('artist'=>$this->defaultValues[3]['artist'])));

        // Verifica o paginator com o padrão
        $paginator = $this->TestDbAdapter->setUsePaginator(true)->fetchAll();
        $paginator = $paginator->toJson();
        $fetchAll = $this->TestDbAdapter->setUsePaginator(false)->fetchAll();
        $this->assertNotEquals(json_encode($this->defaultValues), $paginator);
        $this->assertEquals(json_encode($fetchAll), $paginator);

        // Verifica o paginator alterando o paginator
        $this->TestDbAdapter->getPaginatorConfig()->setPageRange(2)
                                        ->setCurrentPageNumber(1)
                                        ->setItemCountPerPage(2);
        $paginator = $this->TestDbAdapter->setUsePaginator(true)->fetchAll();
        $paginator = $paginator->toJson();
        $this->assertNotEquals(json_encode($this->defaultValues), $paginator);
        $fetchAll = $this->TestDbAdapter->setUsePaginator(false)->fetchAll(null, null, 2);
        $this->assertEquals(json_encode($fetchAll), $paginator);

        // Apaga qualquer cache
        $this->assertTrue($this->TestDbAdapter->getCache()->flush(), 'apaga o cache');

        // Define exibir os delatados
        $this->TestDbAdapter->setShowDeleted(true);

        // Liga o cache
        $this->TestDbAdapter->setUseCache(true);
        $this->assertEquals($this->defaultValues, $this->TestDbAdapter->fetchAll(), 'Igual');
        $this->assertCount(4, $this->TestDbAdapter->fetchAll(), 'Deve conter 4 registros 1');

        // Grava um registro "sem o cache saber"
        $this->TestDbAdapter->getTableGateway()->insert(array('id'=>10, 'artist'=>'nao existo por enquanto', 'title'=>'bla bla', 'deleted' => 0));

        $this->assertCount(4, $this->TestDbAdapter->fetchAll(), 'Deve conter 4 registros 2');
        $this->assertTrue($this->TestDbAdapter->getCache()->flush(), 'apaga o cache');
        $this->assertCount(5, $this->TestDbAdapter->fetchAll(), 'Deve conter 5 registros');

        // Define não exibir os deletados
        $this->TestDbAdapter->setShowDeleted(false);
        $this->assertCount(4, $this->TestDbAdapter->fetchAll(), 'Deve conter 4 registros 3');

        // Apaga um registro "sem o cache saber"
        $this->TestDbAdapter->getTableGateway()->delete(array("id"=>10));
        $this->TestDbAdapter->setShowDeleted(true);
        $this->assertCount(5, $this->TestDbAdapter->fetchAll(), 'Deve conter 5 registros');
        $this->assertTrue($this->TestDbAdapter->getCache()->flush(), 'apaga o cache');
        $this->assertCount(4, $this->TestDbAdapter->fetchAll(), 'Deve conter 4 registros 4');

    }

    /**
     * Tests TestDbAdapter->fetchRow()
     */
    public function testFetchRow()
    {
        // Marca pra usar o campo deleted
        $this->TestDbAdapter->setUseDeleted(true);

        // Verifica os itens que existem
        $this->assertEquals($this->defaultValues[0], $this->TestDbAdapter->fetchRow(1));
        $this->assertEquals($this->defaultValues[1], $this->TestDbAdapter->fetchRow(2));
        $this->assertEquals($this->defaultValues[2], $this->TestDbAdapter->fetchRow(3));

        // Verifica o item removido
        $this->assertNull($this->TestDbAdapter->fetchRow(4));
        $this->TestDbAdapter->setShowDeleted(true);
        $this->assertEquals($this->defaultValues[3], $this->TestDbAdapter->fetchRow(4));
        $this->TestDbAdapter->setShowDeleted(false);
        $this->assertNull($this->TestDbAdapter->fetchRow(4));
    }

    /**
     * Tests TestDbAdapter->fetchAssoc()
     */
    public function testFetchAssoc()
    {
        // O padrão é não usar o campo deleted
        $albuns = $this->TestDbAdapter->fetchAssoc();
        $this->assertCount(4, $albuns, 'showDeleted=false, useDeleted=false');
        $this->assertEquals($this->defaultValues[0], $albuns[1]);
        $this->assertEquals($this->defaultValues[1], $albuns[2]);
        $this->assertEquals($this->defaultValues[2], $albuns[3]);
        $this->assertEquals($this->defaultValues[3], $albuns[4]);

        // Marca para mostrar os removidos e não usar o campo deleted
        $this->TestDbAdapter->setShowDeleted(true)->setUseDeleted(false);
        $this->assertCount(4, $this->TestDbAdapter->fetchAssoc(), 'showDeleted=true, useDeleted=false');

        // Marca pra não mostar os removidos e usar o campo deleted
        $this->TestDbAdapter->setShowDeleted(false)->setUseDeleted(true);
        $this->assertCount(3, $this->TestDbAdapter->fetchAssoc(), 'showDeleted=false, useDeleted=true');

        // Marca pra mostrar os removidos e usar o campo deleted
        $this->TestDbAdapter->setShowDeleted(true)->setUseDeleted(true);
        $albuns = $this->TestDbAdapter->fetchAssoc();
        $this->assertCount(4, $albuns, 'showDeleted=true, useDeleted=true');
        $this->assertEquals($this->defaultValues[0], $albuns[1]);
        $this->assertEquals($this->defaultValues[1], $albuns[2]);
        $this->assertEquals($this->defaultValues[2], $albuns[3]);
        $this->assertEquals($this->defaultValues[3], $albuns[4]);
    }

    /**
     * Tests TestDbAdapter->getFetchAllExtraFields()
     */
    public function testGetFetchAllExtraFields()
    {
        // TODO Auto-generated TestDbAdapterTest->testGetLoader()
        $this->markTestIncomplete("getLoader test not implemented");

        $this->TestDbAdapter->getFetchAllExtraFields(/* parameters */);
    }

    /**
     * Tests TestDbAdapter->getLoader()
     */
    public function testGetLoader()
    {
        // TODO Auto-generated TestDbAdapterTest->testGetLoader()
        $this->markTestIncomplete("getLoader test not implemented");

        $this->TestDbAdapter->getLoader(/* parameters */);
    }

    /**
     * Tests TestDbAdapter->setLoader()
     */
    public function testSetLoader()
    {
        // TODO Auto-generated TestDbAdapterTest->testSetLoader()
        $this->markTestIncomplete("setLoader test not implemented");

        $this->TestDbAdapter->setLoader(/* parameters */);
    }

    /**
     * Tests TestDbAdapter->getTable()
     */
    public function testGetTableGetKey()
    {
        $TestDbAdapter = new TestDbAdapter();
        $this->assertNotNull($TestDbAdapter->getTable());
        $this->assertNotNull($TestDbAdapter->getKey());
        $this->assertEquals('album', $TestDbAdapter->getTable());
        $this->assertEquals('id', $TestDbAdapter->getKey());

        $TestDbAdapter->setTable('outro table');
        $this->assertEquals('outro table', $TestDbAdapter->getTable());

        $TestDbAdapter->setKey('another key');
        $this->assertEquals('another key', $TestDbAdapter->getKey());

        /*
        // @todo permitir chaves compostas
        $TestDbAdapter = new TestDbAdapter('tablename', array('key1', 'key2'));
        $this->assertNotNull($TestDbAdapter->getTable());
        $this->assertNotNull($TestDbAdapter->getKey());
        $this->assertEquals('tablename', $TestDbAdapter->getTable());
        $this->assertEquals(array('key1', 'key2'), $TestDbAdapter->getKey());
         */
    }

    /**
     * Tests TestDbAdapter->getSelect()
     */
    public function testGetSelect()
    {
        // TODO Auto-generated TestDbAdapterTest->testGetSelect()
        $this->markTestIncomplete("getSelect test not implemented");

        $this->TestDbAdapter->getSelect(/* parameters */);
    }

    /**
     * Tests TestDbAdapter->getTableSelect()
     */
    public function testGetTableSelect()
    {
        // TODO Auto-generated TestDbAdapterTest->testGetTableSelect()
        $this->markTestIncomplete("getTableSelect test not implemented");

        $this->TestDbAdapter->getTableSelect(/* parameters */);
    }

    /**
     * Tests TestDbAdapter->fetchCount()
     */
    public function testFetchCount()
    {
        // TODO Auto-generated TestDbAdapterTest->testFetchCount()
        $this->markTestIncomplete("fetchCount test not implemented");

        $this->TestDbAdapter->fetchCount(/* parameters */);
    }

    /**
     * Tests TestDbAdapter->getHtmlSelect()
     */
    public function testGetHtmlSelect()
    {
        // TODO Auto-generated TestDbAdapterTest->testGetHtmlSelect()
        $this->markTestIncomplete("getHtmlSelect test not implemented");

        $this->TestDbAdapter->getHtmlSelect(/* parameters */);
    }

    /**
     * Tests TestDbAdapter->getCache()
     */
    public function testGetCache()
    {
        $this->assertInstanceOf('\Zend\Cache\Storage\Adapter\Filesystem', $this->TestDbAdapter->getCache());
        $this->assertInstanceOf('\Zend\Cache\Storage\Adapter\AbstractAdapter', $this->TestDbAdapter->getCache());
    }

    /**
     * Tests TestDbAdapter->setUseCache()
     */
    public function testGettersSetters()
    {
        $this->assertFalse($this->TestDbAdapter->getUseCache());
        $this->TestDbAdapter->setUseCache(true);
        $this->assertTrue($this->TestDbAdapter->getUseCache());
        $this->TestDbAdapter->setUseCache(false);
        $this->assertFalse($this->TestDbAdapter->getUseCache());

        $this->assertFalse($this->TestDbAdapter->getUsePaginator());
        $this->TestDbAdapter->setUsePaginator(true);
        $this->assertTrue($this->TestDbAdapter->getUsePaginator());
        $this->TestDbAdapter->setUsePaginator(false);
        $this->assertFalse($this->TestDbAdapter->getUsePaginator());

        $this->assertInstanceOf('Realejo\Mapper\PaginatorConfig', $this->TestDbAdapter->getPaginatorConfig());
    }
}
