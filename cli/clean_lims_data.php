<?php
require('base.php');

fwrite(STDOUT,'请输入买方的id:');
$cid = trim(fgets(STDIN)) ?:0;
$customer = O('customer', $cid);
if(!$customer->id) {
    die("未找到对应的买方");
}

$db = Database::factory();

$users = Q("$customer<member user");

$backends = array_keys(Config::get('auth.backends'));

foreach ($users as $user) {
    list(,$user_backend) = Auth::parse_token($user->token);
    if(!in_array($user_backend, $backends)) {
        $user->delete();
    }
    else{
        //清除绑定信息
        $user->is_bind = 0;
        $user->save();
    }
}

$customer->lims_data = null;
$customer->bind_status = 0;
$customer->uuid = 0;
$customer->save();

$db->query("DELETE FROM _r_user_customer WHERE id2={$cid} AND id1 != {$customer->owner->id}");

echo "done\n";
