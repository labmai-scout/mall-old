<?php

class API_Product {

    //RPC 认证函数
    function auth($encrypted_by_server_pubkey, $signed_by_client_privkey, $client_name) {

        if (!$encrypted_by_server_pubkey || !$signed_by_client_privkey || !$client_name) return FALSE;

        $SSL = new OpenSSL();

        //发送过来已经用现在服务器公钥加密了的数据
        $encrypted_by_pubkey =  @base64_decode($encrypted_by_server_pubkey);

        //发送过来用原有服务器私钥签名了的数据
        $signed_by_privkey = @base64_decode($signed_by_client_privkey);

        $clients = (array) Config::get('product.clients');

        //本地的私钥匙
        $local_privkey = Config::get('rpc.private_key');

        if (!isset($clients[$client_name])) {
            return FALSE;
        }

        //远程发送来进行分析的服务器的公钥,通过服务器获取
        $client = $clients[$client_name];
        $client_pubkey = $client['public_key'];

        $decrypted_pubkey_code = $SSL->decrypt($encrypted_by_pubkey, $local_privkey, 'private');


        if (!$SSL->verify($decrypted_pubkey_code, $signed_by_privkey, $client_pubkey)) {
            return FALSE;
        }

        $_SESSION['client_name'] = $client_name;

        //返回用自己私钥签名的数据
        return @base64_encode($SSL->sign($decrypted_pubkey_code, $local_privkey));
    }

    /*
    * 查询返回 token
    */
    function searchProducts($criteria = []) {

        $opt = array();

        if (isset($criteria['keyword'])) {
            $opt['phrase'] = $criteria['keyword'];
        }

        if (isset($criteria['type'])) {
            $opt['type'] = $criteria['type'];
        }

        if (isset($criteria['category'])) {
            $opt['category'] = $criteria['category'];
        }

        if (isset($criteria['name'])) {
            $opt['name'] = $criteria['name'];
        }

        if (isset($criteria['grouped'])) {
            $opt['group_by'] = $criteria['grouped'];
        }

        if (isset($criteria['order_by'])) {
            $opt['order_by'] = $criteria['order_by'];
        }

        $opt_token = 'opt_token_'.uniqid();
        $_SESSION[$opt_token] = $opt;
        $products = new Search_Product($opt);
        $count = $products->total_count();
        $return['token'] = $opt_token;
        $return['total'] = $count;
        return $return;
    }

    function getProducts($token, $start=0, $count=25) {
        $items = array();
        $opt = $_SESSION[$token];
        $products = new Search_Product($opt);
        $products = $products->limit($start, $count);
        foreach ($products as $key => $product) {
            $data = array();
            $data['name'] = $product->name;
            $data['approve_date'] = $product->approve_date;
            $data['publish_date'] = $product->publish_date;
            $data['manufacturer'] = $product->manufacturer;
            $data['catalog_no'] = $product->catalog_no;
            $data['model'] = $product->model;
            $data['type'] = $product->type;
            $data['unit_price'] = $product->unit_price;
            $data['spec'] = $product->spec;
            $data['package'] = $product->package;
            $data['keywords'] = $product->keywords;
            $data['description'] = $product->description;
            $data['stock_status'] = $product->stock_status;
            $data['sale_volume'] = $product->sale_volume;
            $data['brand'] = $product->brand;
            $data['extra'] = $product->_extra;
            $items[$product->id] = $data;
        }

        return $items;
    }

    function getProduct($id) {
        $product = O('product', $id);
        $selling = FALSE;
        if ($product->name() == 'product' && $product->can_buy($avoid_reason)) {
            $selling = TRUE;
        }
        $data = array();
        $data['name'] = $product->name;
        $data['approve_date'] = $product->approve_date;
        $data['publish_date'] = $product->publish_date;
        $data['manufacturer'] = $product->manufacturer;
        $data['catalog_no'] = $product->catalog_no;
        $data['model'] = $product->model;
        $data['unit_price'] = $product->unit_price;
        $data['spec'] = $product->spec;
        $data['package'] = $product->package;
        $data['keywords'] = $product->keywords;
        $data['description'] = $product->description;
        $data['stock_status'] = $product->stock_status;
        $data['sale_volume'] = $product->sale_volume;
        $data['brand'] = $product->brand;
        $data['extra'] = $product->_extra;
        $data['selling'] = $selling;
        $data['vendor_name'] = $product->vendor->name;
        return $data;
    }

    function getProductRatings($id) {
        $product = O('product', $id);
        $rating_summary = Order_Item_Rating_Model::get_rating_summary($product);
        $rating_subjects = Order_Item_Rating_Model::get_product_rating_subjects();
        $return = array();
        $return['summary'] = $rating_summary;
        $return['subjects'] = $rating_subjects;

        return $return;
    }

    function getGroupedProducts($id) {
        $mixed = array();
        return $mixed;
    }
}
