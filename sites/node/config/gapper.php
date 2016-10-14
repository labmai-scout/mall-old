<?php
$config['apps'] = [
   'lab-orders' => [ //app名称，唯一
       'intro' => 'lab-orders', //app简介
       'name' => 'Lab-orders', //app名称
       'title' => 'Lab-orders', //app标题，用于显示在前台页面
       'url' => 'http://orders.node.genee.cn', //这里一定需要配置
       'icon_url' => 'images/gapper.png', //app的图标，当没有获得gapper图标时显示该图标
       'client_id' => 'node-lab-orders', //这里一定需要配置 //app的client_id，用于跳转
   ],
   'mall-vendor' => [ //app名称，唯一
       'intro' => 'mall-vendor', //app简介
       'name' => 'Mall-vendor', //app名称
       'title' => 'Mall-vendor', //app标题，用于显示在前台页面
       'url' => 'http://vendor.genee.cn', //app url
       'icon_url' => 'images/gapper.png', //app的图标，当没有获得gapper图标时显示该图标
       'client_id' => 'mall-vendor', //app的client_id，用于跳转
   ],
   'hazardous-control'=> [
       'intro' => 'hazardous-control', //app简介
       'name' => 'demo-admin-home', //app名称
       'title' => 'hazardous-control', //app标题，用于显示在前台页面
       'url' => 'http://admin.node.genee.cn', //app url
       'icon_url' => 'images/gapper.png', //app的图标，当没有获得gapper图标时显示该图标
       'client_id' => 'node-admin-home', //app的client_id，用于跳转
   ],
];
