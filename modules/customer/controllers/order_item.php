<?php
class Order_Item_Controller extends Layout_Customer_Controller {

	function go_lims_order($id) {
		$me = L('ME');
		$form = Input::form();
		$uuid = Site::get('mall.uuid');
		$data = ['oid'=>$id,'uuid'=>$uuid];

		$order_item = O('order_item', $id);
		$order = $order_item->order;
		$customer = $order->customer;

		if(!$customer->id || !$customer->lims_data) URI::redirect('error/401');
		if ($customer->check_app_installed('lab-orders')) {
			URI::redirect('error/401');
		}

		$lims_data = $customer->lims_data;
		$lims_client_id = $lims_data['client_id'];
		$order_url = URI::url($lims_data['order_url'], $data);

		try{
			$rpc = Gapper::get_RPC();

			$login_token = $rpc->gapper->user->getLoginToken((string)$me->token, $lims_client_id);

			if($login_token) {
				URI::redirect($order_url, ['gapper-token'=>$login_token]);
			}
		}
		catch(Exception $e){}

		URI::redirect($order_url);
	}
}

class Order_Item_AJAX_Controller extends AJAX_Controller {

	function index_show_comment_click() {
		$form = Input::form();

		$comment = O('order_item_comment', $form['comment_id']);

		if ($comment->id) {
			JS::dialog( V('customer:order_item/comment/show', array(
							  'comment' => $comment,
							  )),
						array('title'=>HT('查看评价')));
		}
	}

	function index_post_comment_click() {
		$form = Input::form();

		$item = O('order_item', $form['id']);
		if ($item->order->customer->check_app_installed('lab-orders')) {
			return false;
		}
		$rating_subjects = Order_Item_Rating_Model::get_rating_subjects();


		if ($item->id && $item->can_comment() && L('ME')->is_allowed_to('评价', $item)) {
			JS::dialog( V('customer:order_item/comment/edit', array(
							  'item' => $item,
							  'rating_subjects' => $rating_subjects,
							  )),
						array('title'=>HT('发表评价')));
		}
	}

	function index_post_comment_submit() {
		$form = Form::filter(Input::form());

		$item = O('order_item', $form['id']);
		if ($item->order->customer->check_app_installed('lab-orders')) {
			return false;
		}
		$me = L('ME');

		if ($form['submit'] && $item->id && $item->can_comment() && $me->is_allowed_to('评价', $item)) {

			$content_length_min = Config::get('comment.comment_content_length_min');
			$content_length_max = Config::get('comment.comment_content_length_max');
			$form->validate('content', "length($content_length_min, $content_length_max)",
							HT("评论内容应至少 %min 个字, 最多 %max 个字", array(
								   '%min' => $content_length_min,
								   '%max' => $content_length_max,
								   )));


			$rating_subjects = Order_Item_Rating_Model::get_rating_subjects();
			$valid_rating_values = array(0, 1, 2, 3, 4, 5);
			foreach ($rating_subjects as $key => $label) {
				if (isset($form['ratings'][$key])) {
					$rating_value = $form['ratings'][$key];

					if (in_array($rating_value, $valid_rating_values)) {
						$r = O('order_item_rating');

						$r->subject = $key;
						$r->rating = $rating_value;

						$ratings[$key] = $r;
					}
					else {
						$form->set_error("ratings[$key]", HT('%label 评分有误', array(
												'%label' => $label,
												)));
					}

				}
			}


			if (!$form->no_error) {
				JS::dialog(V('customer:order_item/comment/edit', array(
								 'item' => $item,
								 'form' => $form,
								 'rating_subjects' => $rating_subjects,
								 )),
						   array('title'=>HT('发表评价')));
				return;
			}

			$comment = O('order_item_comment');

			$comment->order_item = $item;
			$comment->author = $me;
			$comment->author_customer = $item->order->customer;
			$comment->content = $form['content'];

			if ($comment->save()) {
				$order = $item->order;
				foreach (Q("{$order->vendor}<member user") as $vendor_member) {
					$item->connect($vendor_member, 'has_news');
				}
				foreach ($ratings as $r) {
					$r->order_item_comment = $comment;
					$r->save();
				}

				JS::refresh();
			}
			else {
				JS::alert(HT('评价保存出错'));
			}

		}
		else {
			JS::redirect('error/401');
		}
	}

    public function index_receive_click() {

        $form = Input::form();
        $item = O('order_item', $form['id']);
		if ($item->order->customer->check_app_installed('lab-orders')) {
			return false;
		}

        if (!$item->id || !L('ME')->is_allowed_to('确认收货', $item->order)) return FALSE;

        //未update item之前的order
        $old_order = $item->order;
        $receive_status = Order_Item_Model::DELIVER_STATUS_RECEIVED;
        $item->receive_date = time();
        $item->receiver = L('ME');
        $item->deliver_status = $receive_status;
        $item->save();

        $unreceived_items_count = Q("order_item[order={$old_order}][deliver_status!=$receive_status]")->total_count();
        if (!$unreceived_items_count) {
        	$old_order->deliver_status = Order_Model::DELIVER_STATUS_RECEIVED;
        	$old_order->save();
        }

        $new_order = ORM_Model::refetch($old_order);

        //只针对item发生变化时，创建一条用于同步的order_activity
        if ($new_order->status == $old_order->status) {
            $activity = O('order_activity');
            $activity->order = $old_order;
            $activity->status = $old_order->status;
            $activity->time = Date::time();
            $activity->save();
        }
        $callback = $old_order->url();
        JS::redirect($callback);

    }
}
