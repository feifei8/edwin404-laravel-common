<?php

namespace Edwin404\Shipping;


use Edwin404\Base\Support\ModelHelper;

class ShippingService
{
    public function listActiveCompanies()
    {
        return ModelHelper::model('shipping_company')->select(['code', 'name'])
            ->where(['active' => true])->orderBy('sort', 'asc')->get()->toArray();
    }

    public function getCompanyNameByCode($companyCode)
    {
        $shippingCompany = ModelHelper::load('shipping_company', ['code' => $companyCode]);
        if (empty($shippingCompany)) {
            return null;
        }
        return $shippingCompany['name'];
    }
}