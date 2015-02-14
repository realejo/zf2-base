<?php
namespace RealejoTest\Mapper;

use Realejo\Mapper\AbstractDbMapper;

class TestDbAdapter extends AbstractDbMapper
{
    protected $table = "album";
    protected $key   = "id";
}
