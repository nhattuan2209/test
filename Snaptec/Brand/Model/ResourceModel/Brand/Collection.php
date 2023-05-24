<?php
namespace Snaptec\Brand\Model\ResourceModel\Brand;


class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Snaptec\Brand\Model\Brand','Snaptec\Brand\Model\ResourceModel\Brand');
    }
}
