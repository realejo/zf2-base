<?php
/**
 * TestDbAdapterTest test case.
 *
 * @link      http://github.com/realejo/libraray-zf2
 * @copyright Copyright (c) 2014 Realejo (http://realejo.com.br)
 * @license   http://unlicense.org
 */
namespace RealejoTest;

use Realejo\App\Model\TestDbAdapter,
    Zend\Db\Adapter\Adapter;

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
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->dropTables();
    }

    /**
     * @return TestDbAdapter
     */
    public function getTestDbAdapter($reset = false)
    {
        if ($this->TestDbAdapter === null || $reset === true) {
            $this->TestDbAdapter = new TestDbAdapter($this->tableName, $this->tableKeyName, $this->getAdapter());
        }
        return $this->TestDbAdapter;
    }

    /**
     * Construct sem nome da tabela
     * @expectedException Exception
     */
    public function testConstructSemTableName()
    {
        new TestDbAdapter(null, $this->tableKeyName);
    }

    /**
     * Construct sem nome da chave
     * @expectedException Exception
     */
    public function testConstructSemKeyName()
    {
        new TestDbAdapter($this->tableName, null);
    }

    /**
     * Constructs the test case copm adapter inválido. Ele deve ser Zend\Db\Adapter\Adapter\AdapterInterface
     * @expectedException Exception
     */
    public function testConstructComAdapterInvalido()
    {
        $TestDbAdapter = new TestDbAdapter($this->tableName, $this->tableKeyName, new \PDO('sqlite::memory:'));
    }

    /**
     * test a criação com a conexão local de testes
     */
    public function testCreateTestDbAdapter()
    {
        $TestDbAdapter = new TestDbAdapter($this->tableName, $this->tableKeyName, $this->getAdapter());
        $this->assertInstanceOf('Realejo\App\Model\TestDbAdapter', $TestDbAdapter);
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
        $this->assertNull($this->getTestDbAdapter()->getOrder());

        // Define uma nova ordem com string
        $this->getTestDbAdapter()->setOrder('id');
        $this->assertEquals('id', $this->getTestDbAdapter()->getOrder());

        // Define uma nova ordem com string
        $this->getTestDbAdapter()->setOrder('title');
        $this->assertEquals('title', $this->getTestDbAdapter()->getOrder());

        // Define uma nova ordem com array
        $this->getTestDbAdapter()->setOrder(array('id', 'title'));
        $this->assertEquals(array('id', 'title'), $this->getTestDbAdapter()->getOrder());
    }


    /**
     * Tests TestDbAdapter->getWhere()
     */
    public function testWhere()
    {

        // Marca pra usar o campo deleted
        $this->getTestDbAdapter()->setUseDeleted(true);

        // Verifica a ordem padrão
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->getTestDbAdapter()->getWhere());
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->getTestDbAdapter()->getWhere(null));
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->getTestDbAdapter()->getWhere(array()));
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->getTestDbAdapter()->getWhere(''));
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->getTestDbAdapter()->getWhere(0));

        $this->assertEquals(array("{$this->tableName}.deleted=1"), $this->getTestDbAdapter()->getWhere(array('deleted'=>true)));
        $this->assertEquals(array("{$this->tableName}.deleted=1"), $this->getTestDbAdapter()->getWhere(array('deleted'=>1)));
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->getTestDbAdapter()->getWhere(array('deleted'=>false)));
        $this->assertEquals(array("{$this->tableName}.deleted=0"), $this->getTestDbAdapter()->getWhere(array('deleted'=>0)));

        $this->assertEquals(array(
            "outratabela.campo=0",
            "{$this->tableName}.deleted=0"
        ), $this->getTestDbAdapter()->getWhere(array('outratabela.campo'=>0)));

        $this->assertEquals(array(
                "outratabela.deleted=1",
                "{$this->tableName}.deleted=0"
        ), $this->getTestDbAdapter()->getWhere(array('outratabela.deleted'=>1)));

        $this->assertEquals(array(
                            "{$this->tableName}.{$this->tableKeyName}=1",
                            "{$this->tableName}.deleted=0"
        ), $this->getTestDbAdapter()->getWhere(array($this->tableKeyName=>1)));

        $dbExpression = new \Zend\Db\Sql\Expression('now()');
        $this->assertEquals(array(
            $dbExpression,
                "{$this->tableName}.deleted=0"
        ), $this->getTestDbAdapter()->getWhere(array($dbExpression)));

    }

    /**
     * Tests campo deleted
     */
    public function testDeletedField()
    {
        // Verifica se deve remover o registro
        $this->assertFalse($this->getTestDbAdapter()->getUseDeleted());
        $this->assertTrue($this->getTestDbAdapter()->setUseDeleted(true)->getUseDeleted());
        $this->assertFalse($this->getTestDbAdapter()->setUseDeleted(false)->getUseDeleted());
        $this->assertFalse($this->getTestDbAdapter()->getUseDeleted());

        // Verifica se deve mostrar o registro
        $this->assertFalse($this->getTestDbAdapter()->getShowDeleted());
        $this->assertFalse($this->getTestDbAdapter()->setShowDeleted(false)->getShowDeleted());
        $this->assertTrue($this->getTestDbAdapter()->setShowDeleted(true)->getShowDeleted());
        $this->assertTrue($this->getTestDbAdapter()->getShowDeleted());
    }

    /**
     * Tests TestDbAdapter->getSQlString()
     */
    public function testGetSQlString()
    {
        // Verfiica o padrão não usar o campo deleted e não mostrar os removidos
        $this->assertEquals('SELECT `album`.* FROM `album`', $this->getTestDbAdapter()->getSQlString(), 'showDeleted=false, useDeleted=false');

        // Marca para usar o campo deleted
        $this->getTestDbAdapter()->setUseDeleted(true);
        $this->assertEquals('SELECT `album`.* FROM `album` WHERE album.deleted=0', $this->getTestDbAdapter()->getSQlString(), 'showDeleted=false, useDeleted=true');

        // Marca para não usar o campo deleted
        $this->getTestDbAdapter()->setUseDeleted(false);

        $this->assertEquals('SELECT `album`.* FROM `album` WHERE album.id=1234', $this->getTestDbAdapter()->getSQlString(array('id'=>1234)));
        $this->assertEquals("SELECT `album`.* FROM `album` WHERE album.texto='textotextotexto'", $this->getTestDbAdapter()->getSQlString(array('texto'=>'textotextotexto')));

    }

    /**
     * Tests TestDbAdapter->testGetSQlSelect()
     */
    public function testGetSQlSelect()
    {
        $select = $this->getTestDbAdapter()->getSQlSelect();
        $this->assertInstanceOf('Zend\Db\Sql\Select', $select);
        $this->assertEquals($select->getSqlString($this->getAdapter()->getPlatform()), $this->getTestDbAdapter()->getSQlString());
    }

    /**
     * Tests TestDbAdapter->fetchAll()
     */
    public function testFetchAll()
    {

        // O padrão é não usar o campo deleted
        $albuns = $this->getTestDbAdapter()->fetchAll();
        $this->assertCount(4, $albuns, 'showDeleted=false, useDeleted=false');

        // Marca para mostrar os removidos e não usar o campo deleted
        $this->getTestDbAdapter()->setShowDeleted(true)->setUseDeleted(false);
        $this->assertCount(4, $this->getTestDbAdapter()->fetchAll(), 'showDeleted=true, useDeleted=false');

        // Marca pra não mostar os removidos e usar o campo deleted
        $this->getTestDbAdapter()->setShowDeleted(false)->setUseDeleted(true);
        $this->assertCount(3, $this->getTestDbAdapter()->fetchAll(), 'showDeleted=false, useDeleted=true');

        // Marca pra mostrar os removidos e usar o campo deleted
        $this->getTestDbAdapter()->setShowDeleted(true)->setUseDeleted(true);
        $albuns = $this->getTestDbAdapter()->fetchAll();
        $this->assertCount(4, $albuns, 'showDeleted=true, useDeleted=true');

        // Marca não mostrar os removios
        $this->getTestDbAdapter()->setShowDeleted(false);

        $albuns = $this->defaultValues;
        unset($albuns[3]); // remove o deleted=1
        $this->assertEquals($albuns, $this->getTestDbAdapter()->fetchAll());

        // Marca mostrar os removios
        $this->getTestDbAdapter()->setShowDeleted(true);

        $this->assertEquals($this->defaultValues, $this->getTestDbAdapter()->fetchAll());
        $this->assertCount(4, $this->getTestDbAdapter()->fetchAll());
        $this->getTestDbAdapter()->setShowDeleted(false);
        $this->assertCount(3, $this->getTestDbAdapter()->fetchAll());

        // Verifica o where
        $this->assertCount(2, $this->getTestDbAdapter()->fetchAll(array('artist'=>$albuns[0]['artist'])));
        $this->assertNull($this->getTestDbAdapter()->fetchAll(array('artist'=>$this->defaultValues[3]['artist'])));

        // Verifica o paginator com o padrão
        $paginator = $this->getTestDbAdapter()->setUsePaginator(true)->fetchAll();
        $paginator = $paginator->toJson();
        $fetchAll = $this->getTestDbAdapter()->setUsePaginator(false)->fetchAll();
        $this->assertNotEquals(json_encode($this->defaultValues), $paginator);
        $this->assertEquals(json_encode($fetchAll), $paginator);

        // Verifica o paginator alterando o paginator
        $this->getTestDbAdapter()->getPaginator()->setPageRange(2)
                                        ->setCurrentPageNumber(1)
                                        ->setItemCountPerPage(2);
        $paginator = $this->getTestDbAdapter()->setUsePaginator(true)->fetchAll();
        $paginator = $paginator->toJson();
        $this->assertNotEquals(json_encode($this->defaultValues), $paginator);
        $fetchAll = $this->getTestDbAdapter()->setUsePaginator(false)->fetchAll(null, null, 2);
        $this->assertEquals(json_encode($fetchAll), $paginator);

        // Apaga qualquer cache
        $this->assertTrue($this->getTestDbAdapter()->getCache()->flush(), 'apaga o cache');

        // Define exibir os delatados
        $this->getTestDbAdapter()->setShowDeleted(true);

        // Liga o cache
        $this->getTestDbAdapter()->setUseCache(true);
        $this->assertEquals($this->defaultValues, $this->getTestDbAdapter()->fetchAll(), 'Igual');
        $this->assertCount(4, $this->getTestDbAdapter()->fetchAll(), 'Deve conter 4 registros 1');

        // Grava um registro "sem o cache saber"
        $this->getTestDbAdapter()->getTableGateway()->insert(array('id'=>10, 'artist'=>'nao existo por enquanto', 'title'=>'bla bla', 'deleted' => 0));

        $this->assertCount(4, $this->getTestDbAdapter()->fetchAll(), 'Deve conter 4 registros 2');
        $this->assertTrue($this->getTestDbAdapter()->getCache()->flush(), 'apaga o cache');
        $this->assertCount(5, $this->getTestDbAdapter()->fetchAll(), 'Deve conter 5 registros');

        // Define não exibir os deletados
        $this->getTestDbAdapter()->setShowDeleted(false);
        $this->assertCount(4, $this->getTestDbAdapter()->fetchAll(), 'Deve conter 4 registros 3');

        // Apaga um registro "sem o cache saber"
        $this->getTestDbAdapter()->getTableGateway()->delete(array("id"=>10));
        $this->getTestDbAdapter()->setShowDeleted(true);
        $this->assertCount(5, $this->getTestDbAdapter()->fetchAll(), 'Deve conter 5 registros');
        $this->assertTrue($this->getTestDbAdapter()->getCache()->flush(), 'apaga o cache');
        $this->assertCount(4, $this->getTestDbAdapter()->fetchAll(), 'Deve conter 4 registros 4');

    }

    /**
     * Tests TestDbAdapter->fetchRow()
     */
    public function testFetchRow()
    {
        // Marca pra usar o campo deleted
        $this->getTestDbAdapter()->setUseDeleted(true);

        // Verifica os itens que existem
        $this->assertEquals($this->defaultValues[0], $this->getTestDbAdapter()->fetchRow(1));
        $this->assertEquals($this->defaultValues[1], $this->getTestDbAdapter()->fetchRow(2));
        $this->assertEquals($this->defaultValues[2], $this->getTestDbAdapter()->fetchRow(3));

        // Verifica o item removido
        $this->assertNull($this->getTestDbAdapter()->fetchRow(4));
        $this->getTestDbAdapter()->setShowDeleted(true);
        $this->assertEquals($this->defaultValues[3], $this->getTestDbAdapter()->fetchRow(4));
        $this->getTestDbAdapter()->setShowDeleted(false);
        $this->assertNull($this->getTestDbAdapter()->fetchRow(4));
    }

    /**
     * Tests TestDbAdapter->fetchAssoc()
     */
    public function testFetchAssoc()
    {
        // O padrão é não usar o campo deleted
        $albuns = $this->getTestDbAdapter()->fetchAssoc();
        $this->assertCount(4, $albuns, 'showDeleted=false, useDeleted=false');
        $this->assertEquals($this->defaultValues[0], $albuns[1]);
        $this->assertEquals($this->defaultValues[1], $albuns[2]);
        $this->assertEquals($this->defaultValues[2], $albuns[3]);
        $this->assertEquals($this->defaultValues[3], $albuns[4]);

        // Marca para mostrar os removidos e não usar o campo deleted
        $this->getTestDbAdapter()->setShowDeleted(true)->setUseDeleted(false);
        $this->assertCount(4, $this->getTestDbAdapter()->fetchAssoc(), 'showDeleted=true, useDeleted=false');

        // Marca pra não mostar os removidos e usar o campo deleted
        $this->getTestDbAdapter()->setShowDeleted(false)->setUseDeleted(true);
        $this->assertCount(3, $this->getTestDbAdapter()->fetchAssoc(), 'showDeleted=false, useDeleted=true');

        // Marca pra mostrar os removidos e usar o campo deleted
        $this->getTestDbAdapter()->setShowDeleted(true)->setUseDeleted(true);
        $albuns = $this->getTestDbAdapter()->fetchAssoc();
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
        $TestDbAdapter = new TestDbAdapter('tablename', 'keyname');
        $this->assertNotNull($TestDbAdapter->getTable());
        $this->assertNotNull($TestDbAdapter->getKey());
        $this->assertEquals('tablename', $TestDbAdapter->getTable());
        $this->assertEquals('keyname', $TestDbAdapter->getKey());

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
        // TODO Auto-generated TestDbAdapterTest->testGetCache()
        $this->markTestIncomplete("getCache test not implemented");

        $this->TestDbAdapter->getCache(/* parameters */);
    }

    /**
     * Tests TestDbAdapter->setUseCache()
     */
    public function testSetUseCache()
    {
        // TODO Auto-generated TestDbAdapterTest->testSetUseCache()
        $this->markTestIncomplete("setUseCache test not implemented");

        $this->TestDbAdapter->setUseCache(/* parameters */);
    }

    /**
     * Tests TestDbAdapter->getUseCache()
     */
    public function testGetUseCache()
    {
        // TODO Auto-generated TestDbAdapterTest->testGetUseCache()
        $this->markTestIncomplete("getUseCache test not implemented");

        $this->TestDbAdapter->getUseCache(/* parameters */);
    }

    /**
     * Tests TestDbAdapter->getPaginator()
     */
    public function testGetPaginator()
    {
        // TODO Auto-generated TestDbAdapterTest->testGetPaginator()
        $this->markTestIncomplete("getPaginator test not implemented");

        $this->TestDbAdapter->getPaginator(/* parameters */);
    }

    /**
     * Tests TestDbAdapter->setUsePaginator()
     */
    public function testSetUsePaginator()
    {
        // TODO Auto-generated TestDbAdapterTest->testSetUsePaginator()
        $this->markTestIncomplete("setUsePaginator test not implemented");

        $this->TestDbAdapter->setUsePaginator(/* parameters */);
    }

    /**
     * Tests TestDbAdapter->getUsePaginator()
     */
    public function testGetUsePaginator()
    {
        // TODO Auto-generated TestDbAdapterTest->testGetUsePaginator()
        $this->markTestIncomplete("getUsePaginator test not implemented");

        $this->TestDbAdapter->getUsePaginator(/* parameters */);
    }
}

