<?php
namespace Snaptec\Brand\Model;


class Brand extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Snaptec\Brand\Model\ResourceModel\Brand');
    }
}
