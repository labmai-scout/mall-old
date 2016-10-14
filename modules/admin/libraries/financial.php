<?php

class Financial {

    static function batch_print($ids) {

        putenv('Q_ROOT_PATH='.ROOT_PATH);
        putenv('SITE_ID='.SITE_ID);

        // 只要脚本符合 1. 无输出 2.后台运行, 就能让 controller 继续
        //setlocale是为了escapeshellarg中可以使用中文
        setlocale(LC_CTYPE, 'UTF8', 'en_US.UTF-8');

        $pdf_name = 'batch_financial_'.date('YmdGis').'.pdf';
        $path = Config::get('system.tmp_dir').'pdf/';
        $keep_alive = $path.$pdf_name.'.keep';

        $cmd = 'php ' . ROOT_PATH . 'cli/batch_financial_pdf.php -s %sids -n %pdf_name > /dev/null 2>&1 &';

        $cmd = strtr($cmd, array(
                          '%sids' => join(',', $ids),
                          '%pdf_name' => $pdf_name,
                          ));

        //先生成keep_alive文件，再去后台执行脚本
        File::check_path($keep_alive);
        touch($keep_alive);
        exec($cmd);
        return $pdf_name;

    }

	static function print_pdf($id=0){
        $statement = O('billing_statement', $id);
        if(!$statement->id) return;
		$me = L('ME');


        putenv('Q_ROOT_PATH='.ROOT_PATH);
        putenv('SITE_ID='.SITE_ID);

        // 只要脚本符合 1. 无输出 2.后台运行, 就能让 controller 继续
        //setlocale是为了escapeshellarg中可以使用中文
        setlocale(LC_CTYPE, 'UTF8', 'en_US.UTF-8');

		$pdf_name = 'ID-'.$statement->id.'-'.date('YmdGis').'.pdf';
        $path = Config::get('system.tmp_dir').'pdf/';
        $keep_alive = $path.$pdf_name.'.keep';

        $cmd = 'php ' . ROOT_PATH . 'cli/batch_financial_pdf.php -s %sids -n %pdf_name > /dev/null 2>&1 &';

        $cmd = strtr($cmd, array(
                          '%sids' => $id,
                          '%pdf_name' => $pdf_name,
                          ));

        File::check_path($keep_alive);
        touch($keep_alive);
        exec($cmd);

        return $pdf_name;
	}

    static function pdf_content($statement, $pdf) {
        if(!$statement->id || !$pdf) return;
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);

        // set font
        $fontname = $pdf->addTTFfont(ROOT_PATH.'/public/fonts/simfang.ttf', 'TrueTypeUnicode', '', 32);

        $pdf->AddPage();

        $pdf->SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 2, 'color' => array(0, 0, 0)));


        $pdf->Line(10, 20, 200, 20, ['width'=>0.5]);
        $pdf->Line(10, 62, 200, 62, ['width'=>0.5]);

        $pdf->SetFont($fontname, '', 14);

        $pdf->MultiCell(25, 10, H(T('登录人:')), 0, 'R', false, 1, 10, 22);
        $pdf->MultiCell(100, 10, H(T('经办人电话:'.Config::get('account.contact_phone'))), 0, 'R', false, 1, 100, 22);
        $pdf->MultiCell(25, 10, H(T('报销单号:')), 0, 'R', false, 1, 10, 38);
        $pdf->MultiCell(25, 10, H(T('业务编号:')), 0, 'R', false, 1, 10, 48);


        $pdf->SetFont($fontname, 'B', 14);
        $pdf->Text(35, 22, H(Config::get('account.operator')));
        $pdf->SetFont('freemono', 'B', 14);

        $pdf->MultiCell(50, 25, H($statement->reserv_no), 0, 'L', 0, 0, 35, 38);
        $pdf->MultiCell(50, 25, H($statement->lsh), 0, 'L', 0, 0, 35, 48);

        $style = [
            'border' => 0,
            'padding' => 0,
            'fgcolor' => [0,0,0],
            'bgcolor' => false, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        ];

        $pdf->SetFont('freemono', 'B', 10);

        $QRCODE = ['YY',
                    $statement->reserv_no, //预约单号
                    Config::get('account.account_no'),
                    Config::get('account.contact_phone'),
                    $statement->balance,
                    $statement->bmbh.'|'.$statement->xmbh //部门编号|项目编号
        ];
        $QRCODE = implode(';', $QRCODE);

        $pdf->write2DBarcode($QRCODE, 'QRCODE,L', 90, 36, 25, 25, $style, 'N');


        $pdf->SetFont('helvetica', '', 10);
        $style = [
            'position' => '',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'cellfitalign' => '',
            'border' => false,
            'hpadding' => 'auto',
            'vpadding' => 'auto',
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //array(255,255,255),
            'text' => true,
            'font' => 'helvetica',
            'fontsize' => 8,
            'stretchtext' => 4
        ];
        $pdf->write1DBarcode($statement->reserv_no, 'C39', 130, 36, '', 15, '', $style, 'N');

        $pdf->SetFont($fontname, 'B', 14);
        $pdf->Text(75, 65, H(T('南开大学日常报销单 (网上商城)')));
        $pdf->SetFont($fontname, '', 10);
        $pdf->Text(175, 70, date('Y').'年'.date('m').'月'.date('d').'日');

        $html = V('admin:financial/statement/pdf', array('statement'=>$statement, 'chinese_balance'=>self::num2rmb($statement->balance)));
        $pdf->SetFont($fontname, '', 14);
        $pdf->writeHTMLCell(30, 30, 12, 75, $html, 0, 1, 0, true, '', true);
    }

    /**
     * 人民币小写转大写
     *
     * @param string $number 数值
     * @param string $int_unit 币种单位，默认"元"，有的需求可能为"圆"
     * @param bool $is_round 是否对小数进行四舍五入
     * @param bool $is_extra_zero 是否对整数部分以0结尾，小数存在的数字附加0,比如1960.30，
     *             有的系统要求输出"壹仟玖佰陆拾元零叁角"，实际上"壹仟玖佰陆拾元叁角"也是对的
     * @return string
     */

    private function num2rmb($number = 0, $int_unit = '圆', $is_round = TRUE, $is_extra_zero = FALSE)
    {
        // 将数字切分成两段
        $parts = explode('.', $number, 2);
        $int = isset($parts[0]) ? strval($parts[0]) : '0';
        $dec = isset($parts[1]) ? strval($parts[1]) : '';

        // 如果小数点后多于2位，不四舍五入就直接截，否则就处理
        $dec_len = strlen($dec);
        if (isset($parts[1]) && $dec_len > 2)
        {
            $dec = $is_round
                    ? substr(strrchr(strval(round(floatval("0.".$dec), 2)), '.'), 1)
                    : substr($parts[1], 0, 2);
        }

        // 当number为0.001时，小数点后的金额为0元
        if(empty($int) && empty($dec))
        {
            return '零';
        }

        // 定义
        $chs = array('0','壹','贰','叁','肆','伍','陆','柒','捌','玖');
        $uni = array('','拾','佰','仟');
        $dec_uni = array('角', '分');
        $exp = array('', '万');
        $res = '';

        // 整数部分从右向左找
        for($i = strlen($int) - 1, $k = 0; $i >= 0; $k++)
        {
            $str = '';
            // 按照中文读写习惯，每4个字为一段进行转化，i一直在减
            for($j = 0; $j < 4 && $i >= 0; $j++, $i--)
            {
                $u = $int{$i} > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位
                $str = $chs[$int{$i}] . $u . $str;
            }
            //echo $str."|".($k - 2)."<br>";
            $str = rtrim($str, '0');// 去掉末尾的0
            $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0
            if(!isset($exp[$k]))
            {
                $exp[$k] = $exp[$k - 2] . '亿'; // 构建单位
            }
            $u2 = $str != '' ? $exp[$k] : '';
            $res = $str . $u2 . $res;
        }

        // 如果小数部分处理完之后是00，需要处理下
        $dec = rtrim($dec, '0');

        // 小数部分从左向右找
        if(!empty($dec))
        {
            $res .= $int_unit;

            // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求
            if ($is_extra_zero)
            {
                if (substr($int, -1) === '0')
                {
                    $res.= '零';
                }
            }

            for($i = 0, $cnt = strlen($dec); $i < $cnt; $i++)
            {
                $u = $dec{$i} > 0 ? $dec_uni[$i] : ''; // 非0的数字后面添加单位
                $res .= $chs[$dec{$i}] . $u;
            }
            $res = rtrim($res, '0');// 去掉末尾的0
            $res = preg_replace("/0+/", "零", $res); // 替换多个连续的0
        }
        else
        {
            $res .= $int_unit . '整';
        }
        return $res;
    }
}
