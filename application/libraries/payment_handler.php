<?php
// (xiaopei.li@2012-08-27) modified

interface Payment_Handler {
    // function __construct(array $opt); // 为什么 Payment_Handler 的 constucter  **必须有**  参数 ??

	function pay($transfer_statement);

	// function get_pay_status($transfer_statement); // 不是所有支付接口都有"支付状态"
	// function get_pending_links($transfer_statement); // 不是所有支付接口都有"待支付状态下的额外连接"

    // function json_encode($data); // 为什么 Payment_Handler **必须有** json_encode ??
    // function json_decode($str); // 为什么 Payment_Handler **必须有** json_decode ??
}
