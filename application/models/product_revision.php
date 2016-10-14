<?php

class Product_Revision_Model extends RObject_Model
{
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

    public static function fetchRPC(array $criteria=[])
    {
        $rpc = self::getRPC();
        try {
            if (isset($criteria['id'])) {
                $data = $rpc->mall->product->getRevision((int)$criteria['id']);
            }
            elseif (isset($criteria['product']) && isset($criteria['version'])) {
                $product = $criteria['product'];
                $data = $rpc->mall->product->getRevision(['product_id'=>$product->id, 'version'=>$criteria['version']]);
            }
            /*
             * 升级之后商品的revision的nodes状态和_extra都被取消了
            foreach ($data['nodes'][SITE_ID] as $key => $value) {
                if (!isset($data[$key])) $data[$key] = $value;
            }
            foreach ($data['_extra'] as $key => $value) {
                if (!isset($data[$key])) $data[$key] = $value;
            }
             */
            return $data;
        }
        catch (Exception $e) {
        }
        return [];
    }
}
