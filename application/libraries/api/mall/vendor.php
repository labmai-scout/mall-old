<?php

class API_Mall_Vendor_Exception extends API_Exception {}

class API_Mall_Vendor {

    function searchVendors($criteria) {
        if(!API_Mall::is_authenticated()) return;

        $result = array();
        $selector = "vendor[approve_date>0][product_count>0]";
        if ($keyword = trim($criteria['keyword'])) {
            $keyword = Q::quote($keyword);
            $selector .= "[name*=$keyword|short_name*=$keyword]";
        }

        if ($criteria['initial']) {
            $initial = $criteria['initial'];
            if ($initial == 'others') {
                $selector .= ':not(vendor[short_abbr^=A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z])';
            }
            else {
		$initial = Q::quote($initial);
                $selector .= "[short_abbr^=$initial]";
            }
        }

	$selector .= ":sort(short_abbr A)";

	// $selector .=":sort(product_count D)";

        $total = Q($selector)->total_count();
        $token = Session::temp_token('vendor.searchVendors_',300);
        $_SESSION[$token] = $selector;
        $result['token'] = $token;
        $result['total'] = $total;
        return $result;
    }

    function getVendors($token, $start = 0, $count = 25) {
        if(!API_Mall::is_authenticated()) return;

        $selector = $_SESSION[$token];
        $vendors = Q($selector)->limit($start, $count);
        $data = array();
        foreach ($vendors as $key => $vendor) {
            $data[$vendor->gapper_group] = array(
                    'id' => $vendor->gapper_group,
                    'name' => $vendor->name,
                    'abbr' => $vendor->short_name,
                    'email' => $vendor->email,
                    'product_total' => $vendor->product_count,
                    'summary' => $vendor->description,
                    'icon' => Config::get('system.base_url').'icon/vendor.'.$vendor->id.'?_='.$vendor->mtime
                );
        }
        return $data;

    }

    function getVendor($id) {
	if(!API_Mall::is_authenticated()) return;
        $vendor = O('vendor', ['gapper_group'=>$id]);
        if (!$vendor->id) return;

        $data = array();
        $now = Date::time();
        $types = Config::get('product.types');
        $configs = array_flip((array)Config::get('mall.mapping_type'));
        $data['id'] = $vendor->gapper_group;
        $data['name'] = $vendor->name;
        $data['abbr'] = $vendor->short_name;
        $data['email'] = $vendor->email;
        $data['fax'] = $vendor->fax;
        $data['phone'] = $vendor->phone;
        $data['summary'] = $vendor->description;
        $data['product_total'] = $vendor->product_count;

        foreach($types as $scope_name => $value) {
            $name = 'product_type.'.$scope_name;
            $main_scope = Q("vendor_scope[vendor={$vendor}][name={$name}][expire_date>{$now}]")->current();

            $mall_scope_name = $configs[$scope_name];
            if ($main_scope->id) {
                $data['scope'][$mall_scop_name] = TRUE;
            }
            else {
                $data['scope'][$mall_scope_name] = FALSE;
            }
        }

        $data['icon'] = Config::get('system.base_url').'icon/vendor.'.$vendor->id.'?_='.$vendor->mtime;

        return $data;
    }

    public function getVendorImage($id=0, $index=0, $size=64) {
        if(!API_Mall::is_authenticated()) return;

        $v = O('vendor', ['gapper_group'=>$id]);
        if (!$v->id) return '';

        /* TODO 因为目前没有做多图功能，故默认返回index为0的首发图片*/
        $size = $v->normalize_icon_size($size);
        if ($icon_file = Core::file_exists(PRIVATE_BASE.'icons/'.$v->name().'/'.$size.'/'.$v->id.'.png', '*')) {
            return Config::get('system.base_url').Cache::cache_file($icon_file).'?_='.$v->mtime;
        }

        return '';
    }

    public function getVendorRatings($id) {
        if(!API_Mall::is_authenticated()) return;
        $vendor = O('vendor', ['gapper_group'=>$id]);
        if (!$vendor->id)  return [];
        $rating_summary = Order_Item_Rating_Model::get_rating_summary($vendor);
        $result = [];
        $result['quality'] = $rating_summary['quality'];
        $result['service'] = $rating_summary['service'];
        $result['delivery'] = $rating_summary['delivery'];
        return $result;
    }

    public function getVendorComments($id, $start=0, $step=5) {
        if(!API_Mall::is_authenticated()) return;
        $vendor = O('vendor', ['gapper_group'=>$id]);
        if (!$vendor->id) return [];
        $comments = Q("$vendor order order_item order_item_comment:sort(ctime D)")->limit($start, $step);
        $results = [];
        foreach ($comments as $comment) {
            $product = $comment->order_item->product;
            $result = [
                'product_id' => $product->id,
                'author_name' => $comment->author->name,
                'author_customer' => $comment->author_customer->name,
                'ctime' => $comment->ctime,
                'content' => $comment->content,
            ];
            $ratings = Q("$comment<order_item_comment order_item_rating");
            $arr = [];
            foreach ($ratings as $r) {
                $arr[$r->subject] = $r->rating;
            }
            $result['ratings'] = $arr;
            $results[$comment->id] = $result;
        }
        return $results;

    }

    public function syncVendorInfo()
    {
        return;
        // 理论上，供应商在mall-vendor的信息是锁定的。所以，也就不能允许被编辑，也就没有同步的必要
        // 协议
        if ($vendor->agreement_time && $vendor->agreement_version===Config::get('vendor.current_agreement_version')) {
        }
        // 基本信息
        // 审核
        if (!$vendor->approve_date) {
        }
        // 拒绝
        if ($vendor->reject_reason) {
        }
        // scope信息
        // 资质图片
        $vendor->license_ready;
        $vendor->get_path('license') . $vendor->license_img;
        $vendor->group_ready;
        $vendor->get_path('group') . $vendor->group_img;
        $vendor->tax_on_land_ready;
        $vendor->get_path('tax_on_land') . $vendor->tax_on_land_img;
        $vendor->state_tax_ready;
        $vendor->get_path('state_tax') . $vendor->state_tax_img;

        $vendor->get_path('special_operate') . $vendor->other_certs[$key]['special_operate_img'];
    }

    // 获取vendor的id和gapper_group
    public function getVendorsIdGroup()
    {
        if(!API_Mall::is_authenticated()) return;
        $vendors = Q('vendor');
        $data = [];
        foreach ($vendors as $vendor) {
            $data[$vendor->id] = $vendor->gapper_group;
        }
        return $data;
    }

}
