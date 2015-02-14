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
namespace Realejo\Mapper;

class PaginatorConfig
{

    protected $PageRange = 10;

    protected $CurrentPageNumber = 1;

    protected $ItemCountPerPage  = 10;

    public function setPageRange($pageRange)
    {
        $this->PageRange = $pageRange;

        // Mantem a cadeia
        return $this;
    }

    public function setCurrentPageNumber($currentPageNumber)
    {
        $this->CurrentPageNumber = $currentPageNumber;

        // Mantem a cadeia
        return $this;
    }

    public function setItemCountPerPage($itemCountPerPage)
    {
        $this->ItemCountPerPage = $itemCountPerPage;

        // Mantem a cadeia
        return $this;
    }

    public function getPageRange()
    {
        return $this->PageRange;
    }

    public function getCurrentPageNumber()
    {
        return $this->CurrentPageNumber;
    }

    public function getItemCountPerPage()
    {
        return $this->ItemCountPerPage;
    }
}