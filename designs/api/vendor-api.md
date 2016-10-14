## LabScout Mall 供应商商品对接文档 (draft 0.2)
基理科技版权所有

### 功能列表
* 供应商身份认证

		vendor/authorize(string clientId, string clientSecret)

* 供应商身份获取

		vendor/whoAmI()

* 供应商提交新的商品

		vendor/addProduct(object productInfo)

* 供应商更新产品资料

		vendor/updateProduct(object productInfo)

* 供应商下架产品

		vendor/revokeProduct(string manufacturer, string catalog_no, string package)

### 接口协议

接口采用基于HTTP传输协议的JSON-RPC 2.0远程调用标准 [JSON-RPC 2.0](http://www.jsonrpc.org/specification)

JSON-RPC是一个轻量级远程过程调用（RPC）协议。协议与具体网络传输环境无关, 因此可以在进程内，或进程间通过套接字, HTTP以及其他多种消息传递环境进行传输。本协议使用JSON数据格式(RFC4627).

在本接口文档中, JSON-RPC 2.0在HTTP的连接上进行RPC调用, 双方的身份验证通过HTTP协议上的基于cookie的session机制实现. 因此在接口调用过程中, 发起的链接必须要保存对方HTTP回应传输来的cookie.

> 注1: 所有RPC调用采用统一的入口地址: e.g. **http://mall.labscout.cn/api**

> 注2: 一些语言的实现中, JSON格式中非ISO8859-1字符(包括中文字符)会直接编码成UCS-2 Unicode格式, 如\u3456, 因此在处理这类解析的时候需要注意, 尽量使用全面实现RFC4627的JSON库.

### 服务器功能接口

#### 供应商身份认证:

* 函数:

    	vendor/authorize(string clientId, string clientSecret)

* 说明:

    在进行后续功能请求前, 客户端必须要先调用该函数进行身份验证, 在本接口中, 该握手协议采用RSA加密+签名的模式进行.

    * $client_id: 当前供应商在商城接口TAB提供的Client ID
    * $client_secret: 当前供应商在商城接口TAB提供的Client Secret

* 返回值:

        boolean: TRUE/FALSE


#### 供应商身份获取:

* 函数:

    	vendor/whoAmI()

* 说明:

    如果成功调用了authorize之后, 调用该方法能够获得当前供应商名称.

* 返回值:

    * `null`: 未授权成功
    * `string`: 供应商名称


#### 供应商提交新的商品

供应商可通过该接口提交新的商品.

* 函数:

        vendor/addProduct(array productInfo)

* 说明:
    * `productInfo`: 为对象结构的商品数据, 包括以下项目:

    	* `name` 			:	商品名称
    	* `manufacturer`	:	生产商(e.g.: SIGMA, Merck, Invitrogen, etc)
    	* `catalog_no`	: 	目录号(货号)
    	* `package`		:	包装( e.g.: 25L, 4x4L, 1000pcs等)
    	* `brand`		    :	品牌
    	* `model` 		:	型号
    	* `spec`			:	规格
    	* `type`			:	类型 (化学试剂: `chem_reagent`， 生物试剂: `bio_reagent`， 耗材: `consumable`)
    	* `keywords`		:	关键字 (用于进行相关检索, 可录入一些产品别名，通俗叫法之类的)
    	* `description`	:	说明 (可填写一些不用于检索但是有助于用户了解产品的文字)
    	* `stock_status`		:	货品状态  int型
			* 0 现货
			* 1 可预订
			* 2 暂无存货
			* 3 停止供货
    	* `unit_price`    :	价格 (人民币报价, 保留两位小数)
        * `orig_price`    : 原价 (人民币报价, 保留两位小数)
    	* `vendor_note`	:	供应商对该商品的备注
		* `supply_time`  : 供货时间 int 以天为单位, 如果数字小于1, 默认为1

        * 化学试剂相关字段
        	* `category`		:	分类 (目前化学试剂部分按照国家通用分类 (2级分类))
            * `rgt_danger_class`: 危险品等级, int 以下是数字代表的危险品类别

                    101 => '具有整体爆炸危险的物质和物品'
                    201 => '易燃气体 '
                    202 => '不燃气体（包括助燃气体）'
                    203 => '有毒气体'
                    301 => '低闪点液体'
                    302 => '中闪点液体'
                    303 => '高闪点液体'
                    401 => '易燃固体'
                    402 => '自燃物品'
                    403 => '遇湿易燃物品'
                    501 => '氧化剂'
                    502 => '有机过氧化物'
                    601 => '有毒品'
                    701 => '放射性物品'
                    801 => '酸性腐蚀品'
                    802 => '碱性腐蚀品'
                    803 => '其他腐蚀品'

            * `rgt_type`: int 1 代表 普通试剂 2 代表 危险化学品 3代表 易制毒化学品
            * `cas_no`: CAS号
            * `rgt_en_name`: 英文名称
            * `rgt_aliases`: 商品别名
            * `reagent_formula`: 分子式
            * `reagent_mw`: 分子量

        * 生物试剂相关字段
        	* `category`		 :	分类 (目前化学试剂部分按照国家通用分类 (2级分类))
        	* `storage_cond`   :	存储条件
        	* `transport_cond` :	运输条件

    * `category`:

        * 化学试剂部分:
            * 精细化工
                * 化工产品
                * 化工原料
                * 助剂
                * 大包装试剂
                * 清洗消毒
                * 硅烷偶联剂

            * 无机试剂
                * 催化剂
                * 硅胶
                * 分子筛
                * 干燥剂
                * 层析
                * 无机盐
                * 酸
                * 碱

            * 生化试剂
                * 抗体
                * 酶类
                * 诊断试剂
                * 糖类
                * 维生素
                * 氨基酸
                * 蛋白质
                * 培养基
                * 核苷酸
                * 生物碱

            * 通用试剂
                * 有机
                * 无机
                * 分析
                * 生化
                * CAS
                * 离子交换

            * 分析试剂
                * 标准品
                * 基准试剂
                * 卡尔费休
                * 气相色谱
                * 液相色谱
                * 指示剂
                * 缓冲剂

            * 环保试剂
                * 环保指示剂
                * 缓冲液
                * 环境测试盒
                * 环保标样
                * 微量分析
                * 环保试纸

            * 有机试剂
                * 杂环化合物
                * 聚合物试剂
                * 离子液体
                * 有机金属
                * 同位素标记
                * 无水试剂

            * 高纯试剂
                * 稀土金属
                * 高纯无机试剂
                * 光谱纯试剂
                * 高纯金属
                * 高纯溶剂
                * 复配试剂

        * 生物试剂部分:
            * 试剂盒
            * 酶类
            * 抗原与抗体
            * 核酸/蛋白电泳与分析试剂
            * 细胞/菌株/载体
            * 细胞/细菌培养试剂
            * 色谱类试剂
            * 氨基酸、多肽与蛋白质
            * 其他实验试剂
            * 抑制剂

* 返回值:
    * 异常: 保存失败, 错误原因可以见JSON-RPC的错误消息
    * 正常: 返回商品在商城的ID

#### 供应商更新产品资料

商城各个供应商可以通过接口对自己的商品进行信息实时更新

如果修改：价格、供货时间、库存；系统支持在更新后，商城状态没有任何变化。（如果商品已有订单，我们支持订单查看到原购买的价格；新的订单按新的价格生成订单）

如果修改：除上述字段外的其他字段，修改完毕后，产品会到“待审核”状态

* 函数:

        vendor/updateProduct(array productInfo)

* 说明:
    * `productInfo`: 为对象结构的商品数据, 包括以下项目:
  		* `name` 			:	商品名称
  		* `manufacturer`	:	生产商(e.g.: SIGMA, Merck, Invitrogen, etc)
  		* `catalog_no`	: 	目录号(货号)
  		* `package`		:	包装( e.g.: 25L, 4x4L, 1000pcs等)
  		* `brand`		    :	品牌
  		* `model` 		:	型号
  		* `spec`			:	规格
  		* `keywords`		:	关键字(用于进行相关检索, 可录入一些产品别名，通俗叫法之类的)
  		* `description`	:	说明(可填写一些不用于检索但是有助于用户了解产品的文字)
    	* `stock_status`		:	货品状态  int型
			* 0 现货
			* 1 可预订
			* 2 暂无存货
			* 3 停止供货
  		* `unit_price`    :	价格(人民币报价, 保留2位小数)
        * `orgi_price`    : 原价(人民币报价, 保留2位小数)
  		* `vendor_note`	:	供应商对该商品的备注
		* `supply_time`  : 供货时间 int 以天为单位, 如果小于1, 系统自动设置为1


* 返回值:
    * 异常: 保存失败, 错误原因可以见JSON-RPC的错误消息
    * 正常: 返回商品在商城的ID


#### 供应商下架商品

商城各个供应商可以通过接口对自己的商品进行信息实时下架
所有你们做下架操作的产品，产品将会到未发布状态。

* 函数:

        vendor/revokeProduct(string manufacturer, string catalogNo, string package)

* 说明:
	* `manufacturer`	:	生产商(e.g.: SIGMA, Merck, Invitrogen, etc)
	* `catalog_no`	: 	目录号(货号)
	* `package`		:	包装( e.g.: 25L, 4x4L, 1000pcs等)


* 返回值:
    * 异常: 保存失败, 错误原因可以见JSON-RPC的错误消息
    * 正常: 返回商品在商城的ID

