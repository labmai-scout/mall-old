<?php

class Cart_Controller extends Layout_Mall_Controller {

    function _before_call($method, &$params) {
        parent::_before_call($method, $params);

        //设定不选定tab
        $this->layout->nav_tabs->select_tab = NULL;
    }

	function index() {

		$me = L('ME');
		if (!$me->is_allowed_to('查看', 'cart')) {
			URI::redirect('error/401');
		}

		$this->add_css('mall:cart');

		$this->layout->title = T('我的购物车');
		$this->layout->body = V('mall:cart/index', array('user'=>$me));
	}

    function checkout() {
        $me = L('ME');

        if (!$me->is_allowed_to('结算', 'cart')) URI::redirect('error/401');

        try {

            $form = Input::form();

            if (!count($form))  throw new Error_Exception;

            if ($form['submit']) {
                $form = Form::filter($form);

                $form->validate('address', 'not_empty', HT('请输入地址'))
                    ->validate('postcode', 'not_empty', HT('请输入邮政编码'))
                    ->validate('phone', 'not_empty', HT('请输入电话'))
                    ->validate('email', 'not_empty', HT('请输入电子邮箱'));

                if (!$form->no_error) throw new Error_Exception;

                $cart = Cart_Model::user_cart($me);
                $orders = $cart->check_out($form);

                if ($orders) {
                    $form_token = Session::temp_token();
                    $_SESSION[$form_token] = array_keys($orders);
                    $callback = URI::url('!mall/cart/checkout', array(
                                'form_token'=> $form_token,
                                'success'=> TRUE
                                ));
                    URI::redirect($callback);
                }
                else {
                    Site::message(Site::MESSAGE_ERROR, HT('提交订单失败! 请联系系统管理员.'));
                }
            }
            elseif ($form['success']) {

                $form_token = Input::form('form_token');
                $order_ids = $_SESSION[$form_token];
                unset($_SESSION[$form_token]);

                if (!$order_ids) {
                    URI::redirect('!mall');
                }

                $this->add_css('mall:cart');

                $this->layout->title = T('提交成功');
                $this->layout->body = V('mall:cart/success', array('order_ids' => $order_ids));
            }
        }
        catch(Error_Exception $e) {
            $this->add_css('mall:cart');

            $this->layout->title = T('生成订单');
            $this->layout->body = V('mall:cart/checkout', ['user' => $me, 'form' => $form]);
        }
    }
}

class Cart_AJAX_Controller extends AJAX_Controller {

	function index_cart_add_click() {
		$me = L('ME');
		$cart = Cart_Model::user_cart($me);

		$form = Input::form();

		$product = O('product', $form['id']);
		if ($product->can_buy($avoid_reason) &&
			$me->is_allowed_to('购买', $product)) {

			JS::dialog(V('mall:add_cart', array('product'=>$product)), array('title'=>T('选择商品数量')));
			Output::$AJAX[] = array(
                    'html'=>(string)V('mall:add_cart', array('product'=>$product)),
                    'text'=>I18N::T('schedule','%name',array('%name'=>$meeting->name,)),
                );
		}
		else {
			JS::alert(HT('加入失败'));
		}
	}

	function index_popover_click() {
		$form = Input::form();
		$product = O('product', $form['id']);
		Output::$AJAX['popover'] = (string)V('mall:cart/add_cart', array('product'=>$product));
	}

	function index_cart_add_submit() {
		$me = L('ME');
		$cart = Cart_Model::user_cart($me);
		$form = Form::filter(Input::form());
		$product = O('product', $form['id']);

		if (!$product->can_buy($avoid_reason) || !$me->is_allowed_to('购买', $product)) return;

		$form->validate('quantity', 'number(>0)', T('商品数量需大于0'));

		if ($form->no_error) {
			$cart->add_item($product, $form['quantity']);

			Output::$AJAX['.cart_item_count'] = array(
				'data' => (string) V('mall:cart/item_count', array('cart' => $cart)),
				'mode' => 'replace',
			);
			Output::$AJAX['div.cart'] = array(
				'data' => (string)V('mall:cart/add_cart_success', array('product'=>$product)),
				'mode' => 'replace',
			);
		}
		else{
			Output::$AJAX['div.cart'] = array(
				'data' => (string)V('mall:cart/add_cart', array('product'=>$product, 'form'=>$form)),
				'mode' => 'replace',
			);
		}	

	}

	function index_cart_remove_click() {
		$me = L('ME');
		$cart = Cart_Model::user_cart($me);
		if (!$me->is_allowed_to('删除项目', $cart)) {
			return;
		}

		$form = Input::form();
		$item = O('cart_item', array('cart'=>$cart, 'id'=>$form['id']));
		if ($item->id) {
			Site::message(HT('%item_name 已从购物车中删除!',
							 array(
								 '%item_name' => $item->product->name
								 )));
			$item->delete();
		}

		JS::refresh();
	}

	function index_empty_cart_click() {
		$me = L('ME');
		$cart = Cart_Model::user_cart($me);
		if (!$me->is_allowed_to('删除项目', $cart)) {
			return;
		}

		if (JS::confirm(HT('您确定要清空购物车么?'))) {
			$items = Q("cart_item[cart={$cart}]");

			foreach ($items as $item) {
				$item->delete();
			}

			Site::message(HT('购物车已清空!'));
		}

		JS::refresh();
	}

	function index_cart_number_blur() {
	// 由于该 input 有 number 类, 加载后 js 会替换 value, 所以若用 change 页面会不断循环刷新
		$me = L('ME');
		$cart = Cart_Model::user_cart($me);

		$form = Input::form();
		$item = O('cart_item', array('cart'=>$cart, 'id'=>$form['id']));
		if ($item->id) {
			$item->quantity = max(0, (int)$form['quantity']);
			if ($item->quantity > 0) {
				$item->save();
			}
			else {
				if (JS::confirm(T('您确定要删除该商品吗？'))) {
				    $item->delete();
				}
			}
		}
	}

	function index_easymade_toxic_add_click() {
		//该功能还未完成，暂时只弹出提示
		JS::alert(HT('暂时未开放购买易制毒'));
	}

    //修改customer时，同步更新地址
    public function index_customer_change() {
        $form = Input::form();
        $customer = O('customer', $form['customer_id']);

        Output::$AJAX['#'. $form['address_id']] = array(
            'data'=> (string) V('mall:cart/address', array(
                'customer'=> $customer,
                'address_id'=> $form['address_id']
            )),
            'mode'=> 'replace',
        );
    }

}
