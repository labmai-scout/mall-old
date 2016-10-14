<?php

class API_Vendor_Exception extends API_Exception {}

class API_Vendor {

    const UNAUTHORIZED = 0;                 //未授权
    const DATA_FLAW = 1;                    //数据不全
    const INVALID_CATEGORY = 2;             //不可用的category
    const INVALID_TYPE = 3;                 //不可用的 type
    const NONEXISTENT_PRODUCT = 4;          //用于修改的product不存在
    const INTERNAL_ERROR = 5;               //内部错误
    const ADD_DENIED = 6;                   //无权限添加
    const EXISTENT_PRODUCT = 7;             //用于添加的product已存在

    static $valid_add_product_fields = array(
        'name',
        'manufacturer',
        'catalog_no',
        'model',
        'spec',
        'package',
        'type',
        'stock_status',
        'unit_price',
        'supply_time',
    );

    static $valid_update_product_fields = array(
        'manufacturer',
        'catalog_no',
        'package',
    );

    //vendor_id vendor的id编号
    //encrypted_by_self_pubkey //client使用服务器的公钥进行加密后的passcode
    //signed_by_client_privkey //client使用自己的私钥进行签名后的passcode
    function authorize($client_id, $client_secret) {
        if (!$client_id || !$client_secret) return FALSE;
        $vendor_api = O('vendor_api', array('client_id'=>$client_id));
        $vendor = $vendor_api->vendor;
        //进行vendor_api vendor是否存在判断
        if ($vendor_api->client_secret != $client_secret || !$vendor->id) return FALSE;
        //验证成功，session保存验证通过的vendor_id
        $_SESSION['vendor_id'] = $vendor->id;

        return true;
    }

    function whoAmI() {
        if (!$_SESSION['vendor_id']) return null;
        $vendor = O('vendor', $_SESSION['vendor_id']);
        if (!$vendor->id) return null;

        return $vendor->name;
    }

    function addProduct($data) {
        if (!$_SESSION['vendor_id']) throw new API_Vendor_Exception(T('未通过身份验证'), self::UNAUTHORIZED);

        $vendor = O('vendor', $_SESSION['vendor_id']);
        if (!$vendor->id) throw new API_Vendor_Exception(T('内部错误'), self::INTERNAL_ERROR);
        if (count(array_diff(self::$valid_add_product_fields, array_keys($data)))) throw new API_Vendor_Exception(T('数据不全'), self::DATA_FLAW);
        $types = Config::get('mall.mapping_type');
        if (!array_key_exists($data['type'], $types)) throw new API_Vendor_Exception(T('不可用的type'), self::INVALID_TYPE);
        $product = O('product', array('vendor'=>$vendor, 'manufacturer'=>$data['manufacturer'], 'catalog_no'=>$data['catalog_no'], 'package'=>$data['package']));

        if ($product->id) {
            throw new API_Vendor_Exception(T('商品已存在'), self::EXISTENT_PRODUCT);
        }
        $criteria = [];
        $criteria['name'] = $data['name'];
        $criteria['vendor_id'] = $vendor->gapper_group;
        $criteria['manufacturer'] = $data['manufacturer'];
        $criteria['catalog_no'] = $data['catalog_no'];
        $criteria['package'] = $data['package'];
        $criteria['model'] = $data['model'];
        $criteria['spec'] = $data['spec'];
        $criteria['brand'] = $data['brand'];
        $criteria['type'] = $data['type'];
        $criteria['unit_price'] = round($data['unit_price'], 2);
        if ($data['orig_price']) $criteria['orig_price'] = round($data['orig_price'], 2);
        if ($data['keywords']) $criteria['keywords'] = $data['keywords'];
        if ($data['type'] == 'chem_reagent') {
            if (isset($data['category'])) $criteria['category'] = $data['category'];
            if (isset($data['rgt_type'])) $criteria['rgt_type'] = $data['rgt_type'];
            if (isset($data['cas_no'])) $criteria['cas_no'] = $data['cas_no'];
            if (isset($data['rgt_en_name'])) $criteria['rgt_en_name'] = $data['rgt_en_name'];
            if (isset($data['rgt_aliases'])) $criteria['rgt_aliases'] = $data['rgt_aliases'];
            if (isset($data['reagent_formula'])) $criteria['reagent_formula'] = $data['reagent_formula'];
            if (isset($data['reagent_mw'])) $criteria['reagent_mw'] = $data['reagent_mw'];
            if (isset($data['rgt_danger_class'])) $criteria['rgt_danger_class'] = $data['rgt_danger_class'];
            if (isset($data['rgt_smiles'])) $criteria['rgt_smiles'] = $data['rgt_smiles'];
        }
        elseif ($data['type'] == 'bio_reagent') {
            if (isset($data['category'])) $criteria['category'] = $data['category'];
            if (isset($data['storage_cond'])) $criteria['storage_cond'] = $data['storage_cond'];
            if (isset($data['transport_cond'])) $criteria['transport_cond'] = $data['transport_cond'];
        }
        elseif ($data['type'] == 'consumable') {
            if (isset($data['consumable_en_name'])) $criteria['consumable_en_name'] = $data['consumable_en_name'];
        }
        $rpc = Product_Model::getRPC();
        $result = $rpc->mall->product->createProduct($criteria);
        /*
        增加节点信息
        */
        $criteria = [];
        if ($result['id']) {
            $id = (int)$result['id'];
            $node = SITE_ID;
            $criteria2 = [
                'status' => Product_Model::STATUS_PENDING,
                'stock_status' => (int)$data['stock_status'],
                'supply_time' => (int)$data['supply_time'],
            ];
            $result2 = $rpc->mall->product->setProductNode($id, $node, $criteria2);
        }
        if ($result['id']) {
            return (int)$result['id'];
        }
        else {
            throw new API_Vendor_Exception(T(implode(',', $result['error_msg'])), self::INTERNAL_ERROR);
        }
    }

    function revokeProduct($manufacturer, $catalog_no, $package) {
        if (!$_SESSION['vendor_id']) throw new API_Vendor_Exception(T('未通过身份验证'), self::UNAUTHORIZED);

        $vendor = O('vendor', $_SESSION['vendor_id']);
        if (!$vendor->id) throw new API_Vendor_Exception(T('内部错误'), self::INTERNAL_ERROR);

        $data = [];
        $data['vendor_id'] = $vendor->gapper_group;
        $data['manufacturer'] = $manufacturer;
        $data['catalog_no'] = $catalog_no;
        $data['package'] = $package;
        $rpc = Product_Model::getRPC();
        $data['nodes'][SITE_ID] = [
            'status' => Product_Model::STATUS_DELETE,
        ];
        $result = $rpc->mall->product->updateProduct(0, $data);
        if ($result['id']) {
            $log = strtr('%remote_address通过接口, 下架了%vendor_name[%vendor_id] 的product: %product[%product_id]', array(
            '%remote_address'=>$_SERVER['REMOTE_ADDR'],
            '%vendor_name'=>$vendor->name,
            '%vendor_id'=>$vendor->id,
            '%product'=>$product->name,
            '%product_id'=>$product->id
            ));
            Log::add($log, 'api');
            return (int)$result['id'];
        }
        else {
            throw new API_Vendor_Exception(T(implode(',', $result['error_msg'])), self::INTERNAL_ERROR);
        }
        return false;
    }
    function updateProduct($data) {

        if (!$_SESSION['vendor_id']) throw new API_Vendor_Exception(T('未通过身份验证'), self::UNAUTHORIZED);

        $vendor = O('vendor', $_SESSION['vendor_id']);
        if (!$vendor->id) throw new API_Vendor_Exception(T('内部错误'), self::INTERNAL_ERROR);

        foreach (self::$valid_update_product_fields as $value) {
            if (!$data[$value]) throw new API_Vendor_Exception(T('数据不全'), self::DATA_FLAW);
        }

        $criteria = [];
        $criteria['nodes'][SITE_ID] = [
            'stock_status' => $data['stock_status'],
            'supply_time' => $data['supply_time'],

        ];
        $criteria['vendor_id'] = $vendor->gapper_group;
        $criteria['manufacturer'] = $data['manufacturer'];
        $criteria['catalog_no'] = $data['catalog_no'];
        $criteria['package'] = $data['package'];
        if ($data['spec']) $criteria['spec'] = $data['spec'];
        if ($data['brand']) $criteria['brand'] = $data['brand'];
        if ($data['model']) $criteria['model'] = $data['model'];
        if ($data['keywords']) $criteria['keywords'] = $data['keywords'];
        if ($data['category']) $criteria['category'] = $data['category'];
        if ($data['unit_price']) $criteria['unit_price'] = $data['unit_price'];
        if ($data['orig_price']) $criteria['orig_price'] = $data['orig_price'];
        if ($data['type']) $criteria['type'] = $data['type'];
        if ($data['type'] == 'chem_reagent') {
            if ($data['cas_no']) $criteria['cas_no'] = $data['cas_no'];
            if ($data['rgt_en_name']) $criteria['rgt_en_name'] = $data['rgt_en_name'];
            if ($data['rgt_aliases']) $criteria['rgt_aliases'] = $data['rgt_aliases'];
            if ($data['rgt_type']) $criteria['rgt_type'] = $data['rgt_type'];
            if ($data['rgt_danger_class']) $criteria['rgt_danger_class'] = $data['rgt_danger_class'];
            if ($data['reagent_formula']) $criteria['reagent_formula'] = $data['reagent_formula'];
            if ($data['reagent_mw']) $criteria['reagent_mw'] = $data['reagent_mw'];
        }
        elseif ($data['type'] == 'bio_reagent') {
            if ($data['transport_cond']) $criteria['transport_cond'] = $data['transport_cond'];
            if ($data['storage_cond']) $criteria['storage_cond'] = $data['storage_cond'];
        }
        elseif ($data['type'] == 'consumable') {
            if ($data['consumable_en_name']) $criteria['consumable_en_name'] = $data['consumable_en_name'];
        }
        $rpc = Product_Model::getRPC();
        $result = $rpc->mall->product->updateProduct(0, $criteria);
        if ($result['id']) {
            return $result['id'];
        }
        else {
            throw new API_Vendor_Exception(T(implode(',', (array)$result['error_msg'])), self::INTERNAL_ERROR);
        }
        return false;
    }
}
