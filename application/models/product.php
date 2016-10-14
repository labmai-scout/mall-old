<?php

class Product_Model extends RObject_Model
{

    // 待审核
    const STATUS_PENDING = 1;
    // 销售中
    const STATUS_ON_SALE = 2;
    // 审核失败
    const STATUS_FAILED  = 3;
	const STATUS_DELETE  = 4;


    const STOCK_STATUS_IN_STOCK = 0;
    const STOCK_STATUS_BOOKABLE = 1;
    const STOCK_STATUS_NO_STOCK = 2;
    const STOCK_STATUS_STOP_SUPPLY = 3;

    static $stock_status = array(
        self::STOCK_STATUS_IN_STOCK => '现货',
        self::STOCK_STATUS_BOOKABLE => '可预订',
        self::STOCK_STATUS_NO_STOCK => '暂无存货',
        self::STOCK_STATUS_STOP_SUPPLY => '停止供货',
    );

    protected $object_page = array(
        'admin_view'=>'!admin/product/products/view.%id[.%arguments]',
        'admin_edit'=>'!admin/product/products/edit.%id[.%arguments]',
        'admin_delete'=>'!admin/product/products/delete.%id[.%arguments]',
        'admin_approve'=>'!admin/product/products/approve.%id[.%arguments]',
    );

    private static $_RPC = [];
    public static function getRPC($type='hub-product')
    {
        $type = "mall.{$type}";
        if (self::$_RPC[$type]) return self::$_RPC[$type];
        try {
            $config = Config::get($type);
            $clientID = $config['client_id'];
            $clientSecret = $config['client_secret'];
            $rpc = new RPC($config['api']);
            $bool = $rpc->mall->authorize($clientID, $clientSecret);
            self::$_RPC[$type] = $rpc;
            return $rpc;
        }
        catch (Exception $e) {
        }
    }

    public static function vendorApprove($vid)
    {
        if (!$vid) return false;
        $data = [
            'vid' => $vid,
            'nodes' => [
                SITE_ID => [
                    'status' => self::STATUS_ON_SALE,
                ],
            ],
        ];
        $rpc = self::getRPC();
        return $rpc->mall->product->batchApproveProduct($data);
    }

    function canBuy( &$avoid_reason=null ) {
        return $status = $this->selling;
    }

    public function getVersion()
    {
        if (!$this->id) return false;
        $rpc = self::getRPC();
        return $rpc->mall->product->checkVersion($this->id);
    }

    public static function batchApprove($pids)
    {
        if (!$pids) return false;
        $data = [
            'pids' => $pids,
            'nodes' => [
                SITE_ID => [
                    'status' => self::STATUS_ON_SALE,
                ],
            ],
        ];
        $rpc = self::getRPC();
        return $rpc->mall->product->batchApproveProduct($data);
    }

    public function approve()
    {
        $rpc = self::getRPC();
        $data = [];
        $data['nodes'][SITE_ID] = [
            'status' => self::STATUS_ON_SALE,
        ];
        $return = $rpc->mall->product->updateProduct($this->id, $data);
        return $return['id']?:false;
    }

    function new_url() {
        $id = $this->id;
        $mp_url = Config::get('mall.new_url');
        return URI::url($mp_url.'/product/'.$id, ['oauth-sso'=>'mall.nankai']);
    }

    static function get_types() {
        static $_types;
        if (!isset($_types)) {
            $weights;
            $otypes = (array)Config::get('product.types');
            foreach($otypes as $type => $name) {
                if (is_string($name)) {
                    $otypes[$type] = array('name'=>$name, 'weight'=>Config::get("product.types.$type.weight"));
                }
            }

            uasort($otypes, function($a, $b) {
                return $a['weight'] > $b['weight'];
            });

            foreach($otypes as $type => $o) {
                $_types[$type] = $o['name'];
            }
        }
        return $_types;
    }

    public function unapprove($reason)
    {
        $rpc = self::getRPC();
        $data = [];
        $data['nodes'][SITE_ID] = [
            'status' => self::STATUS_FAILED,
            'reject_reason' => $reason,
        ];
        $return = $rpc->mall->product->updateProduct($this->id, $data);
        return $return['id']?:false;
    }

    function get_price($customer, $quantity=1) {
        //TODO calculate discount by customer tags
        $price = $this->unit_price;
        if ($this->unit_price > 0) {
            $price = floatval($this->unit_price * $quantity);
        }

        return $price;
    }

    public function soldout($reason)
    {
        $rpc = self::getRPC();
        $data = [];
        $data['nodes'][SITE_ID] = [
            'status' => self::STATUS_FAILED,
            'reject_reason' => $reason,
        ];
        $return = $rpc->mall->product->updateProduct($this->id, $data);
        return $return['id']?:false;
    }

    public static function getProducts($start=0, $perpage=20, $status='unapproved', $keywords=null)
    {
        $rpc = self::getRPC();
        $criteria = [
            'q'=> $keywords,
        ];
        if ($status == 'unapproved') {
            $criteria['status'] = self::STATUS_PENDING;
        }
        elseif ($status == 'approved') {
            $criteria['status'] = self::STATUS_ON_SALE;
        }
        try {
            $info = $rpc->mall->product->searchProducts($criteria);
            $dps = $rpc->mall->product->getProducts($info['token'], $start, $perpage);
            $products = [];
            foreach ($dps as $id => $dp) {
                $products[$id] = (object) [
                    'id' => $id,
                    'vendor_id'  => $dp['vendor'],
                    'orig_price' => $dp['orig_price'],
                    'unit_price' => $dp['price'],
                    'name' => $dp['name'],
                    'manufacturer' => $dp['manufacturer'],
                    'catalog_no' => $dp['catalog_no'],
                    'model' => $dp['model'],
                    'spec' => $dp['spec'],
                    'package' => $dp['package'],
                    'keywords' => $dp['keywords'],
                    'description' => $dp['description'],
                    'brand' => $dp['brand'],
                    'version' => $dp['version'],
                    'dirty' => $dp['dirty'],
                    'type' => $dp['type'],
                    'category' => $dp['category'],
                    'sale_volume' => $dp['sale_volume'],
                    'status' => $dp['status'],
                    'stock_status' => $dp['stock_status'],
                    'supply_time'  => $dp['supply_time'],
                ];
            }

            $result = [
                'data'=> $products,
                'total'=> $info['total'],
                'count'=> count($dps)
            ];
        } catch (Exception $e) {

        }
        return $result;
    }

    public static function getVendorProducts($start=0, $perpage=20, $tab='unapproved', $vid = 0 ,$keywords=null)
    {
        $rpc = self::getRPC();
        $criteria = [
            'nodes'=> SITE_ID,
            'q'=> $keywords,
            'vendor_id' => $vid,
        ];

        if ($tab == 'unapproved') {
            $criteria['status'] = self::STATUS_PENDING;
        }
        elseif ($tab == 'approved') {
            $criteria['status'] = self::STATUS_ON_SALE;
        }
        elseif ($tab == 'reagent') {
            $criteria['template'] = 'chem_reagent';
        }
        elseif ($tab == 'biologic_reagent') {
            $criteria['template'] = 'bio_reagent';
        }
        elseif ($tab == 'consumable') {
            $criteria['template'] = 'consumable';
        }

        try {
            $info = $rpc->mall->product->searchProducts($criteria);
            $dps = $rpc->mall->product->getProducts($info['token'], $start, $perpage);
            $products = [];
            foreach ($dps as $id => $dp) {
                $products[$id] = (object) [
                    'id' => $id,
                    'vendor_id'  => $dp['vendor'],
                    'orig_price' => $dp['orig_price'],
                    'unit_price' => $dp['price'],
                    'name' => $dp['name'],
                    'manufacturer' => $dp['manufacturer'],
                    'catalog_no' => $dp['catalog_no'],
                    'model' => $dp['model'],
                    'spec' => $dp['spec'],
                    'package' => $dp['package'],
                    'keywords' => $dp['keywords'],
                    'description' => $dp['description'],
                    'brand' => $dp['brand'],
                    'version' => $dp['version'],
                    'dirty' => $dp['dirty'],
                    'type' => $dp['type'],
                    'category' => $dp['category'],
                    'sale_volume' => $dp['sale_volume'],
                    'status' => $dp['status'],
                    'stock_status' => $dp['stock_status'],
                    'supply_time'  => $dp['supply_time'],
                ];
            }

            $result = [
                'data'=> $products,
                'total'=> $info['total'],
                'count'=> count($dps)
            ];
        } catch (Exception $e) {

        }
        return $result;
    }

    public function getRevisions($start=0, $perpage=20)
    {
        $rpc = self::getRPC();
        try {
            $data = $rpc->mall->product->getRevisions($this->id, $start, $perpage);
            $result = [];
            foreach ((array)$data['data'] as $key => $info) {
                $revisions[] = (object) [
                    'id' => $key,
                    'product_id' => $info['product_id'],
                    'vendor_id' => $info['vendor_id'],
                    'unit_price' => $info['unit_price'],
                    'vendor_note' => $info['vendor_note'],
                    'mtime' => $info['mtime'],
                    'name' => $info['name'],
                    'manufacturer' => $info['manufacturer'],
                    'catalog_no' => $info['catalog_no'],
                    'model' => $info['model'],
                    'spec' => $info['spec'],
                    'package' => $info['package'],
                    'keywords' => $info['keywords'],
                    'description' => $info['description'],
                    'brand' => $info['brand'],
                    'type' => $info['type'],
                    'category' => $info['category'],
                    'version' => $info['version'],
                    'extra' => $info['_extra'],
                ];
            }
            $result = [
                'total' => $data['total'],
                'count' => $data['count'],
                'revisions' => $revisions,
            ];
            return $result;
        } catch (Exception $e) {
        }
        return [];
    }

    public static function fetchRPC($criteria=[])
    {
        $criteria = (array) $criteria;
        $rpc = self::getRPC();
        try {
            if (isset($criteria['id'])) {
                $data = $rpc->mall->product->getProduct((int)$criteria['id']);
                $data['vendor_id'] = $data['vendor'];
                $data['vendor'] = O('vendor', ['gapper_group'=>$data['vendor']]);
                $data['unit_price'] = $data['price'];
                return $data;
            }
        }
        catch (Exception $e) {
        }
        return [];
    }
}
