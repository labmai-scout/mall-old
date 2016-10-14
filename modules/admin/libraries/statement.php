<?php

class Statement {

    static function batch_print($ids) {

        putenv('Q_ROOT_PATH='.ROOT_PATH);
        putenv('SITE_ID='.SITE_ID);

        // 只要脚本符合 1. 无输出 2.后台运行, 就能让 controller 继续
        //setlocale是为了escapeshellarg中可以使用中文
        setlocale(LC_CTYPE, 'UTF8', 'en_US.UTF-8');

        $pdf_name = 'batch_statement_'.date('YmdGis').'.pdf';
        $path = Config::get('system.tmp_dir').'pdf/';
        $keep_alive = $path.$pdf_name.'.keep';

        $cmd = 'php ' . ROOT_PATH . 'cli/batch_statement_pdf.php -s %sids -n %pdf_name > /dev/null 2>&1 &';

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
        $statement = O('transfer_statement', $id);
        if(!$statement->id || !$statement->can_print_pdf()) return;
		$me = L('ME');


        putenv('Q_ROOT_PATH='.ROOT_PATH);
        putenv('SITE_ID='.SITE_ID);

        // 只要脚本符合 1. 无输出 2.后台运行, 就能让 controller 继续
        //setlocale是为了escapeshellarg中可以使用中文
        setlocale(LC_CTYPE, 'UTF8', 'en_US.UTF-8');

        $pdf_name = 'ID-'.$id.'-'.date('YmdGis').'.pdf';
        $path = Config::get('system.tmp_dir').'pdf/';
        $keep_alive = $path.$pdf_name.'.keep';

        $cmd = 'php ' . ROOT_PATH . 'cli/batch_statement_pdf.php -s %sids -n %pdf_name > /dev/null 2>&1 &';

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
        $pdf->Line(10, 92, 200, 92, ['width'=>0.5]);

        $pdf->SetFont($fontname, '', 14);

        $pdf->MultiCell(15, 10, H(T('买方:')), 0, 'R', false, 1, 10, 28);
        $pdf->MultiCell(25, 10, H(T('买方电话:')), 0, 'R', false, 1, 10, 38);

        $pdf->MultiCell(25, 10, H(T('部门编号:')), 0, 'R', false, 1, 10, 48);
        $pdf->MultiCell(25, 10, H(T('项目编号:')), 0, 'R', false, 1, 10, 58);

        $pdf->MultiCell(15, 10, H(T('金额:')), 0, 'R', false, 1, 10, 68);

        $pdf->MultiCell(15, 10, H(T('日期:')), 0, 'R', false, 1, 10, 78);

        $pdf->SetFont($fontname, 'B', 14);
        $pdf->Text(25, 28, H($statement->customer->owner->name).'('.H($statement->customer->name).')');
        $pdf->SetFont('freemono', 'B', 14);
        $pdf->MultiCell(50, 25, H($statement->customer->owner->phone), 0, 'L', 0, 0, 35, 38);
        $pdf->MultiCell(50, 25, H($statement->bmbh), 0, 'L', 0, 0, 35, 48);
        $pdf->MultiCell(50, 25, H($statement->xmbh), 0, 'L', 0, 0, 35, 58);
        $pdf->MultiCell(50, 25, number_format(floatval($statement->balance), 2), 0, 'L', 0, 0, 25, 68);
        $pdf->MultiCell(80, 25, Date::format($statement->ctime, 'Y/m/d'), 0, 'L', 0, 0, 25, 78);


        $pdf->SetFont($fontname, 'B', 14);
        $pdf->Text(90, 40, H(T('预约号:')));
        $style = [
            'border' => 0,
            'padding' => 0,
            'fgcolor' => [0,0,0],
            'bgcolor' => false, //array(255,255,255)
            'module_width' => 1, // width of a single module in points
            'module_height' => 1 // height of a single module in points
        ];

        $pdf->SetFont('freemono', 'B', 10);

        //YY;YYDH;FZRBH;SJH;JE;BMBH|XMBH
        //YY固定；YYDH=预约单号；FZRBH=职工号；SJH=手机号；JE=金额；BMBH=部门编号；XMBH=项目编号；
        //经确认，职工号为工资号
        $QRCODE = ['YY',
                    $statement->reserv_no, //预约单号
                    $statement->customer->account_no, //工资号
                    $statement->customer->owner->phone, //手机号
                    $statement->balance, //金额
                    $statement->bmbh.'|'.$statement->xmbh //部门编号|项目编号
        ];
        $QRCODE = implode(';', $QRCODE);

        $pdf->write2DBarcode($QRCODE, 'QRCODE,L', 110, 36, 13, 13, $style, 'N');


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

        $site_title = Config::get('page.title_default');
        $pdf->SetFont($fontname, 'B', 14);
        $pdf->Text(75, 100, H(T('%site转账凭证', ['%site'=>$site_title])).'(#'.$statement->id.')');

        $html = V('admin:transfer/statement/pdf', array('statement'=>$statement));
        $pdf->SetFont($fontname, '', 14);
        $pdf->writeHTMLCell(30, 30, 12, 115, $html, 0, 1, 0, true, '', true);
    }
}
