<?php
class Item_Controller extends Controller {
    function export($id) {
        $pdf = new TCPDF();
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        $pdf->AddPage();
        $pdf->SetFont('cid0cs', '', 20);
        $item = O('order_item', $id);
        if (!$item->id) URI::redirect('error/401');
        $voucher = $item->order->voucher;
        $pid = $item->product_id;
        $node = SITE_ID;
        // e.g. http://qr.labmai.com/order/njust/M1231231/123141
        $base_url = rtrim(Config::get('vendor.bind_wechat_url'), '/');
        $url = "$base_url/order/$node/$voucher/$pid";
        $style = array(
            'border' => 0,
            'padding' => 0,
            'fgcolor' => array(0,0,0),
            'bgcolor' => false,
            'module_width' => 1,
            'module_height' => 1
        );
        $pdf->write2DBarcode($url, 'QRCODE,L', 70, 30, 60, 60, $style, 'N');
        $pdf->Output('订单商品二维码-'.$id.'.pdf', 'I');
    }
}