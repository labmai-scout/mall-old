#Lims用户升级
##目标
针对南开大学 lab 服务器上的 所有未升级为 lab-orders 的 lims 用户升级
###升级内容
* 用户/组 升级
* 订单升级
* 经费升级
* 存货升级

##流程
### 1. 准备数据
1. mall-old 找到所有没有升级 gapper 相关 买方帐号 [gapper_group]
2. 定位这些买方帐号对应的 lims 站点 [site id]
3. mall-old/lims cli 创建目录 contract_upgrade 存放所有升级脚本

### 2. 用户/组 升级 (基于原有的升级脚本修改)

1. 确保 lims 中的用户都推送到 mall-old
2. mall-old 编写脚本针对 合同用户的成员全部升级为 gapper 用户, 买方升级为 gappper 组, 并安装 nankai-lab-orders, lab-grant, lab-inventory, nankai-drug-precursor-plan 这几个应用
3. 获得这些升级的 gapper 组

### 订单升级 (基于原有的升级脚本修改)
1. 针对2.3得到的数据进行lab-orders 的升级
2. 买方信息升级: 工资号, 送货地址, 权限 ...
3. mall 订单升级
4. lims 自购订单升级
5. mall 付款单升级

###经费升级
> 升级代码位置 lims/cli/contract_upgrade/
>
> 可能需要通过用户邮箱来定位具体的人员

1. 人员权限升级 user_permission
2. 数据升级
	* 经费 (grant)
	* 经费用途 (grant_portion)
	* 经费花费 (grant_expense)
	* 花费和订单关联 (order->expense ---> grant_expense)
3. 开发 lims 点进经费管理跳转相关应用

###操作流程

lims

	SITE_ID=lab LAB_ID=nankai_admin php db.php
	SITE_ID=lab LAB_ID=nankai_admin php user.php
mall

	SITE_ID=nankai php user.php
	SITE_ID=nankai php user.php

lab-orders

   	gini mall upgrade customer sync lab-gapper-id
	gini mall upgrade order remote lab-gapper-id
	gini mall upgrade order limslocal lab-gapper-id
	gini mall upgrade statement fetch lab-gapper-id

lims

	SITE_ID=lab LAB_ID=nankai_admin php lab.php
	SITE_ID=lab LAB_ID=nankai_admin php grant.php
	SITE_ID=lab LAB_ID=nankai_admin php expense.php

lab-orders

	cp expense.php output inito lab-orders/grants.php
	gini mall upgrade grants create

lims

	SITE_ID=lab LAB_ID=nankai_admin php stock.php

lab-orders

	gini mall upgrade order limsvendor lab-gapper-id