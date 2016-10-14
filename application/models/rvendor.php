<?php

class RVendor_Model extends RObject_Model
{

	protected $object_page = array(
        'admin_view'=>'!admin/vendor/view.%id[.%arguments]',
        'logo'=> '!admin/vendor/image.%id.logo',
        'license'=> '!admin/vendor/image.%id.license',
        'org'=> '!admin/vendor/image.%id.org',
        'national'=> '!admin/vendor/image.%id.national',
        'regional'=> '!admin/vendor/image.%id.regional',
        'extra'=> '!admin/vendor/image.%id.extra[.%arguments]',
    );

    private static $_RPC = [];
    public static function getRPC($type='hub-vendor')
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

    public static function getUnderApproval($start=0, $perpage=20, $keywords=null)
    {
        $rpc = self::getRPC();
        try {
            $info = $rpc->mall->vendor->searchVendors([
                'node_id'=> SITE_ID,
                'keyword'=> $keywords,
                'status'=> 'applying'
            ]);
            $data = $rpc->mall->vendor->getVendors($info['token'], $start, $perpage);
            $result = [
                'data'=> $data,
                'total'=> $info['total'],
                'count'=> count($data)
            ];
        }
        catch (Exception $e) {
            $result = [];
        }
        return $result;
    }

    public static function getApproved($start=0, $perpage=20, $keywords=null)
    {
        $rpc = self::getRPC();
        try {
            $info = $rpc->mall->vendor->searchVendors([
                'node_id'=> SITE_ID,
                'keyword'=> $keywords,
                'status'=> 'approved'
            ]);
            $tmp = json_encode($info);
            $data = $rpc->mall->vendor->getVendors($info['token'], $start, $perpage);
            $result = [
                'data'=> $data,
                'total'=> $info['total'],
                'count'=> count($data)
            ];
        }
        catch (Exception $e) {
            $tmp = $e->getMessage();
            $result = [];
        }
        return $result;
    }

    public static function fetchRPC(array $criteria=[])
    {
        $rpc = self::getRPC();
        try {
            if (isset($criteria['id'])) {
                return $rpc->mall->vendor->getVendor((int)$criteria['id']);
            }
        }
        catch (Exception $e) {
        }
        return [];
    }

    // TODO pihizi
    public function getCertImage($type, $index=null)
    {
        $rpc = self::getRPC();
        try {
            $content = $rpc->mall->vendor->getCertImage($this->id, $type, $index);
            $content = base64_decode($content);
        }
        catch (Exception $e) {
        }
        return $content;
    }

    private static $_scopes_list = null;

    private function getScopesList()
    {
        if (self::$_scopes_list) {
            return self::$_scopes_list;
        }
        $rpc = self::getRPC('hub-node');
        try {
            $result = $rpc->mall->node->getScopes();
            self::$_scopes_list = $result;
        }
        catch (\Exception $e) {
        }
        return $result;
    }

    public function getScopes($needAll=false)
    {
        $rpc = self::getRPC();
        $scopesList = $this->getScopesList();
        try {
            $result = $rpc->mall->vendor->getVendorNode($this->id, SITE_ID);
            $scopes = (array)$result['scopes'];
            $data = [];
            foreach ($scopes as $scope=>$info) {
                if (!$needAll && $info['status']=='rejected') continue;
                if ($info['status']=='approved') {
                    $data[$scope] = [
                        'status'=> 'approved',
                        'title'=> $scopesList[$scope],
                        'valid_period'=> $info['valid_period'],
                    ];
                }
                else if ($info['status']=='rejected') {
                    $data[$scope] = [
                        'status'=> 'rejected',
                        'title'=> $scopesList[$scope],
                        'expirty_date'=> $info['expirty_date'],
                        'reason'=> $info['reason']
                    ];
                }
                else if ($info['status']=='applying') {
                    $data[$scope] = [
                        'status'=> 'applying',
                        'title'=> $scopesList[$scope]
                    ];
                }
            }
        }
        catch (Exception $e) {
        }
        return $data;
    }

    public function getRequestingScopes()
    {
        $rpc = self::getRPC();
        $scopesList = $this->getScopesList();
        try {
            $result = $rpc->mall->vendor->getVendorNode($this->id, SITE_ID);
            $scopes = (array)$result['scopes'];
            $data = [];
            foreach ($scopes as $scope=>$info) {
                if ($info['status']=='applying') {
                    $data[$scope] = $scopesList[$scope];
                }
            }
        }
        catch (Exception $e) {
        }
        return $data;
    }

    public function ignore($message)
    {
        $scopes = $this->getScopes();
        $data = [];
        foreach ($scopes as $scope=>$info) {
            $data[$scope] = [
                'status'=> 'rejected',
                'expire_date'=> date('Y-m-d H:i:s'),
                'reason'=> $message
            ];
        }
        $rpc = self::getRPC();
        try {
            if (!empty($data)) {
                $config = Config::get('mall.hub-vendor');
                $clientID = $config['client_id'];
                $result = $rpc->mall->vendor->setVendorNode($this->id, SITE_ID, [
                    'scopes'=> $data
                ]);
                $rpc2 = self::getRPC('hub-product');
                $result2 = $rpc2->mall->product->batchStopSellingProduct($this->id, SITE_ID);
            }
        }
        catch (Exception $e) {
        }
        return $result && $result2;
    }

    public function approveSets($needApprove=[], $needReject=[])
    {
        $rpc = self::getRPC();
        try {
            $data = [];
            $raw = $this->getScopes(true);
            foreach ($needApprove as $scope=>$info) {
                $data[$scope] = [
                    'status'=> 'approved',
                    'valid_period'=> [
                        $info['from'], $info['to']
                    ]
                ];
            }
            foreach ($needReject as $scope=>$info) {
                $data[$scope] = [
                    'status'=> 'rejected',
                    'expirty_date'=> date('Y-m-d H:i:s'),
                    'reason'=> $info
                ];
            }
            foreach ($raw as $scope=>$info) {
                if (isset($data[$scope])) continue;
                $data[$scope] = $info;
            }
            $result = $rpc->mall->vendor->setVendorNode($this->id, SITE_ID, [
                'scopes'=> $data
            ]);
        }
        catch (Exception $e) {
            return false;
        }
        $this->refreshScopes();
        return $result;
    }

    function create($fromID=null)
    {
        $id = $fromID ?: $this->id;
        $rpc = self::getRPC();
        try {
            $info = $rpc->mall->vendor->getVendor($id);
            if (!is_array($info) || empty($info)) {
                return;
            }
            $vendor = O('vendor');
            $vendor->name = $info['name'];
            if ($info['abbr']) {
                $vendor->short_name = $info['abbr'];
                //$venodr->short_abbr = Pinyin::code($info['abbr']);
            }
            $vendor->phone = $info['phone'];
            $vendor->fax = $info['fax'];
            $vendor->email = $info['email'];
            $vendor->homepage = $info['website'];
            $vendor->address = $info['address'];
            $vendor->description = $info['summary'];
            $vendor->owner_name = $info['legal_person_name'];
            $vendor->manager_name = $info['gm_name'];
            $vendor->manager_phone = $info['gm_phone'];
            $vendor->contact_name = $info['contact_name'];
            $vendor->contact_phone = $info['contact_phone'];
            $vendor->bank_name = $info['bank_name'];
            $vendor->bank_account = $info['bank_account'];
            $vendor->scope = $info['scope'];
            $vendor->operation_due = strtotime($info['scope_expiry_date']);
            $vendor->license_no = $info['license_no'];
            $vendor->license_valid_date = strtotime($info['next_yearly_inspect_date']);
            $vendor->license_last_valid_date = strtotime($info['prev_yearly_inspect_date']);
            $vendor->group_no = $info['cert_org_no'];
            $vendor->group_valid_date = strtotime($info['cert_org_next_year_inspect_date']);
            $vendor->group_dt = strtotime($info['cert_org_scope_expiry_date']);
            $vendor->tax_on_land_no = $info['cert_regional_no'];
            $vendor->state_tax_no = $info['cert_national_no'];
            $vendor->capital = $info['capital'];
            $vendor->establish_date = strtotime($info['reg_date']);
            $vendor->agreement_time = strtotime($info['ctime']);
            $vendor->agreement_version===Config::get('vendor.current_agreement_version');

                $gid = $info['group'];
                $config = Config::get('mall.gapper');
                $clientID = $config['client_id'];
                $clientSecret = $config['client_secret'];
                $rpc = new RPC($config['api']);
                $bool = $rpc->gapper->authorize($clientID, $clientSecret);
                if (!$bool) return;
                $group = $rpc->gapper->group->getInfo((int)$gid);
                // 用户不需要安装app，也可以获取跳转用的token，所以，就不需要强制给供应商安装app了
                //$rpc->gapper->app->installTo($clientID, 'group', (int)$group['id']);
            $vendor->gapper_group = $group['id'];
                $username = $group['creator'];
                $data = $rpc->gapper->user->getInfo($username);
                $uid = (int)$data['id'];
                $user = O('user', ['gapper_user'=> $uid]);
                if (!$user->id) {
		  			 $user = Gapper::create_user($uid);
		   			if (!$user->id) {
		      			return;
		   			}
                }
            if (!$user || !$user->id) {
                return;
            }
            $vendor->owner = $user;
            if ($vendor->save()) {
                $vendor->connect($vendor->owner, 'member');
                return $vendor;
            }
            /*
            $options = Vendor_Model::$nemployees_options;
            $vendor->nemployees = $info[''];
            'employee_count'=> $options[$vendor->nemployees],
             */
        }
        catch (Exception $e) {
        }
    }

    // 与hub-vendor同步scopes信息
    function refreshScopes()
    {
        $vendor = O('vendor', ['gapper_group'=>$this->id]);
        if (!$vendor->id) {
            $vendor = $this->create();
        }

        if (!$vendor || !$vendor->id) return;

        $result = (array) $this->getScopes();

        $trans = [
            // product_type.computer
            // product_type.servers
            // product_type.service
            // 'product_type.reagent'=> 0,
            'rgt_type.1'=> 'chem_reagent', // 1
            'rgt_type.2'=> 'chem_reagent.hazardous', // 4
            'rgt_type.3'=> 'chem_reagent.drug_precursor', // 2
            'rgt_type.4'=> 'chem_reagent.highly_toxic',
            'rgt_type.5'=> 'chem_reagent.explosive',
            'rgt_type.6'=> 'chem_reagent.psychotropic',
            'rgt_type.7'=> 'chem_reagent.narcotic',
            'product_type.biologic_reagent'=> 'bio_reagent', // 8
            'product_type.consumable'=> 'consumable' // 16
        ];
        $scopes = Q("vendor_scope[vendor={$vendor}]");
        $approved = 0;
        $applying = 0;
        $my = [];
        $scopeReagent = null;
        $reagentFrom = null;
        $reagentTo = null;
        foreach ($scopes as $scope) {
            $k = $scope->name;
            if ($k=='product_type.reagent') {
                $scopeReagent = $scope;
                continue;
            }
            $v = $trans[$k];
            $my[] = $v;
            if (isset($result[$v]) && $result[$v]['status']=='approved') {
                $scope->expire_date_from = strtotime($result[$v]['valid_period'][0]);
                $scope->expire_date = strtotime($result[$v]['valid_period'][1]);
                $scope->save();
                $approved++;
                if (in_array($v, ['chem_reagent', 'chem_reagent.drug_precursor', 'chem_reagent.hazardous', 'chem_reagent.highly_toxic', 'chem_reagent.explosive', 'chem_reagent.psychotropic', 'chem_reagent.narcotic'])) {
                    $reagentFrom = $reagentFrom ? min($scope->expire_date_from, $reagentFrom) : $scope->expire_date_from;
                    $reagentTo = max($scope->expire_date, $reagentTo);
                }
                continue;
            }
            $scope->expire();
            $scope->delete();
        }

        $rts = array_flip($trans);
        $need = array_diff(array_keys($result), $my);
        foreach ($need as $k) {
            if (!isset($result[$k]['status'])) {
                continue;
            }
            if ($result[$k]['status']=='applying') {
                $applying++;
                continue;
            }
            if ($result[$k]['status']=='rejected') {
                continue;
            }
            $name = $rts[$k];
            $scope = O('vendor_scope', [
                'vendor'=> $vendor,
                'name'=> $name
            ]);
            $scope->vendor = $vendor;
            $scope->name = $name;
            $scope->expire_date_from = strtotime($result[$k]['valid_period'][0]);
            $scope->expire_date = strtotime($result[$k]['valid_period'][1]);
            $scope->save();
            if (in_array($k, ['chem_reagent', 'chem_reagent.drug_precursor', 'chem_reagent.hazardous', 'chem_reagent.highly_toxic', 'chem_reagent.explosive', 'chem_reagent.psychotropic', 'chem_reagent.narcotic'])) {
                $reagentFrom = $reagentFrom ? min($scope->expire_date_from, $reagentFrom) : $scope->expire_date_from;
                $reagentTo = max($scope->expire_date, $reagentTo);
            }
            $approved++;
        }

        if ($scopeReagent) {
            if (!$reagentFrom && !$reagentTo) {
                $scopeReagent->expire();
                $scopeReagent->delete();
            }
            else {
                if ($scopeReagent->expire_date_from!=$reagentFrom || $scopeReagent->expire_date!=$reagentTo) {
                    $scopeReagent->expire_date_from = $reagentFrom;
                    $scopeReagent->expire_date = $reagentTo;
                    $scopeReagent->save();
                    $approved++;
                }
            }
        }
        else {
            if ($reagentFrom || $reagentTo) {
                $scope = O('vendor_scope', [
                    'vendor'=> $vendor,
                    'name'=> 'product_type.reagent'
                ]);
                $scope->vendor = $vendor;
                $scope->name = 'product_type.reagent';
                $scope->expire_date_from = $reagentFrom;
                $scope->expire_date = $reagentTo;
                $scope->save();
                $approved++;
            }
        }

        if ($approved) {
            if (!$vendor->approve_date) {
                $vendor->approve();
            }
        }
        else if ($applying) {
            if ($vendor->approve_date) {
                $vendor->unapprove();
            }
        }
        // 没有待审核也没有已审核，取消供应商的发布状态
        else {
            if ($vendor->approve_date || $vendor->publish_date) {
                $vendor->unpublish('检测到供应商没有待审核和已审核通过的销售类别，自动进行了unpublish');
                Admin::cli_unapprove_products($vendor);
            }
        }
    }

}
