
### 签订协议

#### 配置文件存放在 sites/`SITE_ID`/config/vendor.php
```php
<?php

# 协议版本
$config['current_agreement_version'] = 'V1';
# 协议生效日期
$config['current_agreement_date_start'] = '2014-12-01 00:00:00';

```

#### 协议内容存放 sites/`SITE_ID`/agreement/`$config['current_agreement_version']`.md

#### 执行清理脚本，下架到期没有签订协议的vendor
```shell
SITE_ID=nankai php cli/check_vendor_agreement.php
```
