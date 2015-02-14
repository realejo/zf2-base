<?php
/**
 * PaginatorTest test case.
 *
 * @link      http://github.com/realejo/libraray-zf2
 * @copyright Copyright (c) 2014 Realejo (http://realejo.com.br)
 * @license   http://unlicense.org
 */
namespace RealejoTest\Mapper;

use Realejo\Mapper\PaginatorConfig;

class PaginatorConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var PaginatorConfig
     */
    private $PaginatorConfig;


    /**
     * @return Base
     */
    public function getPaginatorConfig()
    {
        if ($this->PaginatorConfig === null) {
            $this->PaginatorConfig = new PaginatorConfig();
        }
        return $this->PaginatorConfig;
    }

    /**
     * getPageRange
     */
    public function testGetPageRange()
    {
        // Recupera o Page Range
        $page = $this->getPaginatorConfig()->getPageRange();

        // Verifica se o conteudo veio correto
        $this->assertEquals(10, $page);
        $this->assertTrue(is_int($page));
        $this->assertFalse(is_string($page));
    }

    /**
     * getCurrentPageNumber
     */
    public function testGetCurrentPageNumber()
    {
        // Recupera o Current Page Number
        $page = $this->getPaginatorConfig()->getCurrentPageNumber();

        // Verifica se o conteudo veio correto
        $this->assertEquals(1, $page);
        $this->assertTrue(is_int($page));
        $this->assertFalse(is_string($page));
    }

    /**
     * getItemCountPerPage
     */
    public function testGetItemCountPerPage()
    {
        // Recupera o Item Count Per Page
        $page = $this->getPaginatorConfig()->getItemCountPerPage();

        // Verifica se o conteudo veio correto
        $this->assertEquals(10, $page);
        $this->assertTrue(is_int($page));
        $this->assertFalse(is_string($page));
    }

    /**
     * setPageRange
     */
    public function testSetPageRange()
    {
        // Recupera o Page Range
        $page = $this->getPaginatorConfig()->getPageRange();

        // Verifica se o conteudo veio correto
        $this->assertEquals(10, $page);
        $this->assertTrue(is_int($page));
        $this->assertFalse(is_string($page));

        // Define o valor do Page Range
        $page = $this->getPaginatorConfig()->setPageRange(15);

        // Verifica se o conteudo veio correto
        $this->assertTrue(is_object($page));

        // Recupera o Page Range
        $page = $this->getPaginatorConfig()->getPageRange();

        // Verifica se o conteudo veio correto
        $this->assertEquals(15, $page);
        $this->assertTrue(is_int($page));
        $this->assertFalse(is_string($page));
    }

    /**
     * setCurrentPageNumber
     */
    public function testSetCurrentPageNumber()
    {
        // Recupera o Current Page Number
        $page = $this->getPaginatorConfig()->getCurrentPageNumber();

        // Verifica se o conteudo veio correto
        $this->assertEquals(1, $page);
        $this->assertTrue(is_int($page));
        $this->assertFalse(is_string($page));

        // Define o Current Page Number
        $page = $this->getPaginatorConfig()->setCurrentPageNumber(2);

        // Verifica se o conteudo veio correto
        $this->assertTrue(is_object($page));

        // Recupera o Current Page Number
        $page = $this->getPaginatorConfig()->getCurrentPageNumber();

        // Verifica se o conteudo veio correto
        $this->assertEquals(2, $page);
        $this->assertTrue(is_int($page));
        $this->assertFalse(is_string($page));
    }

    /**
     * setItemCountPerPage
     */
    public function testSetItemCountPerPage()
    {
        // Recupera o Item Count Per Page
        $page = $this->getPaginatorConfig()->getItemCountPerPage();

        // Verifica se o conteudo veio correto
        $this->assertEquals(10, $page);
        $this->assertTrue(is_int($page));
        $this->assertFalse(is_string($page));

        // Recupera o Item Count Per Page
        $page = $this->getPaginatorConfig()->setItemCountPerPage(20);

        // Verifica se o conteudo veio correto
        $this->assertTrue(is_object($page));

        // Recupera o Item Count Per Page
        $page = $this->getPaginatorConfig()->getItemCountPerPage();

        // Verifica se o conteudo veio correto
        $this->assertEquals(20, $page);
        $this->assertTrue(is_int($page));
        $this->assertFalse(is_string($page));
    }
}

