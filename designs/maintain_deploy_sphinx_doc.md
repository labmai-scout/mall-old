#运维部署升级脚本操作流程
##工作任务
本次任务主要有两部分内容

1. mysql 数据升级
2. sphinx 索引更新

##升级前要做的事情

发布 mall hotfix/release1.7.1

原因： 保证线上数据的 vendor_product 和 order的 mtime 可以实时更新，为了在新版本的 sphinx 索引刷新的时候实现无缝切换做数据准备用。

##mysql 数据升级
###数据结构的调整

由于新版本的商城取消了产品的概念，所以商城中部分数据结构需要调整，脚本务必按照顺序执行

1. `00-drop_table_product.php`  删除 product表
2. `05-rename_vendor_product_to_product.php`
3. `10-drop_table_product_column_product.php` 删除 product 表的 product 字段
4. `20-chang_table_order_item_column.php` 修改order_item 的 vendor_product 字段为 product 字段
5. `30-product_icon_change.php` 针对 icon图片的处理，由于将所有的 vendor_product 更名为 product,所以对应的图片文件目录也要进行修正

###product 类别拆分数据升级

1. `25-prod_reagent_split_biologic_reagent.php`
由于商城之前没有开启生物试剂模块，很多供应商将生物试剂加到实验试剂中，并将规格设置为"生物试剂"作为区分，我们将这部分数据提出来，修正分类，并将规格设置未' '(规格不能为空)

##sphinx 索引更新(无缝切换)
###工作流程
1. (服务器 B)假设线上环境的服务器为**服务器 A**, 运维部门创建用来刷新索引的**服务器 B**
2. (服务器 B)部署服务器 B 中的代码环境，版本为新版本的 mall
3. (服务器 B)停掉服务器 B 的 searchd服务，通过 mall 中 cli 脚本 get_sphinx.php 获得 sphinx 结构，替换sphinx 索引配置文件(一般路径在 /etc/sphinxsearch/conf.d/mall_nankai 中)，如果没有配置文件则新建。删除服务器 B 旧的索引文件(一般路径在 /var/lib/sphinxsearch/data/mall_nankai 下的所有文件。如果没有 mall_nankai 这个文件夹需要新建，不然之后的服务启动会报错)，以上操作完毕后启动 searchd 服务。验证是否部署成功(`mysql -h127.0.0.1 -P 9306` 之后`show tables` 查看表是否都建立成功);
4. (服务器 A)运维人员登录服务器 A，dump 生产环境数据**mall_nankai.sql**，并记录 dump 操作**开始时间**(注意是开始时间不是结束时间，时间精确到分钟即可)。
5. (服务器 B)将 mall_nankai.sql 转移到服务器 B 中，登录服务器 B，删除对应的 database(一般为 mall_nankai)，再新建，导入从 服务器 A 中dump 出的数据(本地测试真实数据（1.2G）导入大约需要一小时左右时间)。

	* `drop database mall_nankai` 
	* `create database mall_nankai`
	* `use mall_nankai`
	* `source 目标 SQL 文件`
6. (服务器 B)执行数据升级脚本，以适应新的代码环境。
7. (服务器 B) 执行 mall 中 cli 脚本sphinx_update.php。（耗时很长...）
8. (服务器 B)索引执行完毕后，停掉 sphinx 服务，拷贝两部分数据
	* 索引配置文件 (/etc/sphinxsearch/conf.d/mall_nankai)
	* 索引存储文件 ( /var/lib/sphinxsearch/data/mall_nankai)
	
	将拷贝文件传至 服务器 A
9. (服务器 A)正式开始进行线上版本升级(需要挂维护页面)，停掉 sphinx 服务
10. (服务器 A)打包新版本的代码，cli 进行 mysql 数据升级。
11. (服务器 A)数据升级完毕后，拷贝在服务器 B 中生成的配置文件和索引文件到服务器 A 中对应的文件进行替换，启动 sphinx 服务。
12. （服务器 A）完成步骤11后，线上商城就可以正常使用了，此时需要执行增加数据索引刷新脚本`sphinx_incremental_data_update.php` 需要使用在步骤4中记录的时间，可以以此提前一小时保障数据完全.
	* SITE_ID=nankai php sphinx_incremental_data_update.php
	* 输入时间 时间书写格式 XXXX-XX-XX XX:XX:XX（例: 2014-04-03 10:12:00）
13. 切换完成。

##部署测试环境运维需知
###数据导出流程并缩小数据量

**导出表结构**
	
	mysqldump -uroot -p83719730 -d mall_nankai >db.sql
	
**导出基本数据表数据**

需要运维人员去目标数据库对进行 `show tables`去找出不需要缩减数据的表执行指令

`mysqldump -uroot -p83719730 database table table table table ... >1.sql`
	
举例

	mysqldump -uroot -p83719730 ljl_mall_nankai _auth _config _r_order_billing_bucket _r_order_billing_statement _r_product_category_product _r_tag_customer _r_transfer_bucket_order _r_transfer_statement_order _r_user_customer _r_user_order _r_user_role _r_vendor_product_product_category _r_vendor_user _remember_login billing_bucket billing_statement cart cart_item category comment customer customer_member_perm deliver_address distributor except_order message news order order_activity order_count order_item order_item_comment  product_category recovery role tag transfer_bucket transfer_statement user vendor vendor_api vendor_scope >1.sql

**导出大数据的表的小数据 **

像 product 这样的大表可以通过 --where 进行小规模导出
`mysqldump -uroot -p83719730 database table --where "1=1 limit 10000" > table.sql`
这样就可以导出限量为10000条的数据

	mysqldump -uroot -p83719730 ljl_mall_nankai product --where "1=1 limit 10000" > product.sql


导入目标数据库

1. create database mall_nankai
2. use mall_nankai
3. source db.sql 导入表结构的 sql 文件
4. source 1.sql 导入小数据表的文件
5. source product.sql 导入limit 数据量的 sql 文件