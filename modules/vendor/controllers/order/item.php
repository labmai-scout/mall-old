<?php

class Order_Item_AJAX_Controller extends AJAX_Controller {

	function index_show_comment_click() {
		$form = Input::form();
		$me = L('ME');
		$comment = O('order_item_comment', $form['comment_id']);
		$item = $comment->order_item;
		$item->disconnect($me, 'has_news');
		if ($comment->id) {
			JS::dialog( V('vendor:order_item/comment/show', array(
							  'comment' => $comment,
							  )),
						array('title'=>HT('查看评价')));
		}
	}
	
	function index_reply_comment_submit() {
		$form = Form::filter(Input::form());

		$comment = O('order_item_comment', $form['id']);
		$me = L('ME');

		if ($form['submit'] && $comment->id && $comment->can_reply() && $me->is_allowed_to('回复', $comment)) {

			$reply = O('order_item_comment_reply');

			$reply_length_min = Config::get('comment.reply_length_min');
			$reply_length_max = Config::get('comment.reply_length_max');
			$form->validate('content', "length($reply_length_min, $reply_length_max)",
							HT("评价回复应至少 %min 个字, 最多 %max 个字", array(
								   '%min' => $reply_length_min,
								   '%max' => $reply_length_max,
								   )));

			if ($form->no_error) {
				$reply->order_item_comment = $comment;
				$reply->author = $me;
				$reply->content = $form['content'];

				if (!$reply->save()) {
					JS::alert(HT('评价保存出错'));
				}
			}

			JS::dialog( V('vendor:order_item/comment/show', array(
							  'comment' => $comment,
							  'form' => $form,
							  )),
						array('title'=>HT('查看评价')));

		}
		else {
			JS::redirect('error/401');
		}
	}

    public function index_deliver_click() {

        $form = Input::form();
        $item = O('order_item', $form['id']);

        if (!$item->id || !L('ME')->is_allowed_to('确认发货', $item->order)) return FALSE;

        $item->deliver_date = Date::time();
        $item->deliver_status = Order_Item_Model::DELIVER_STATUS_DELIVERED;

        $item->save();

        $order = $item->order;

        $delivered_status = Order_Item_Model::DELIVER_STATUS_DELIVERED;
        //如果所有的order_item都为已发货，则order也已发货
        if (!Q("order_item[order={$order}][deliver_status!=$delivered_status]")->total_count()) {
            if ($order->deliver_status != Order_Model::DELIVER_STATUS_DELIVERED) {
                $order->deliver();
            }
        }
        else {
            //触发order的save，自动生成revision
            $product = $item->product();
            $now = new \Datetime();
            $now = $now->format('Y-m-d H:i:s');
            $order->mall_description = [
                'a'=>H(T('**:user(:vendor)** **发货** 了个别商品', [
                                ':user'=>L('ME')->name,
                                ':vendor'=>$order->vendor->short_name
                            ])),
                't'=>$now,
                'u'=>L('ME')->gapper_user,
                'd'=>'发货了: ['.$product->name.'](product/'.$product->id.'/'.$item->version.')'
            ];
            $order->save();
        }

        $callback = $item->order->url(NULL, NULL, NULL, 'vendor_view');
        JS::redirect($callback);
    }

    /**
	 * 
	 * @brief 单个打印ajax
	 * params item->id type='single'
	 * @return [type] [description]
	 * 
	 */
	function index_single_export_click(){

		$form = Input::form();
		if (is_array($form)) {
			$item_id = $form['item_id'];
			if (isset($form['item_id'])) {
			
				JS::dialog( V('vendor:order_item/print/batch_print', array(
								'item_id' => $item_id,
								'type' => 'single'
				  			)),
						array('title'=>HT('选择打印的位置数量')));
			}
		}
		
	}

	/**
	 * @brief 订单打印全选AJAX
	 * @return [type] [description]
	 */
	function index_batch_exports_submit(){

		$form = Input::form();
		
		if ($form['submit']) {
			if (is_array($form['select']) && count($form['select'])){
				if (JS::confirm(HT('您确定打印这些清单么?'))) {
					
					JS::dialog( V('vendor:order_item/print/batch_print', array(
								'select' => $form['select'],
				  			)),
						array('title'=>HT('选择打印的位置')));
				}
			}else{
				JS::alert(HT('请选择要打印的清单'));
			}
		}
	}
}
