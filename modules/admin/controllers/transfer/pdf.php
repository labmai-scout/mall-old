<?php
class Transfer_Pdf_Controller extends _Layout_Controller {

	function view($id=0){
        $file = Statement::print_pdf($id);
        $this->layout = V('admin:transfer/statement/waiting_pdf', array('file'=>$file));
    }

    function batch_print(){
    	$form = Input::form();
		$select = (array)$form['select'];

		if(!count($select)) return;
		array_filter($select, function($value){return ($value == 'on');});
		$statement_ids = array_keys($select);

        $file = Statement::batch_print($statement_ids);
        $this->layout = V('admin:transfer/statement/waiting_pdf', array('file'=>$file));
    }

    function print_all() {
    	$statement_ids = Q('transfer_statement[reserv_no][status='.Transfer_Statement_Model::STATUS_PENDING_TRANSFER.']')->to_assoc('id','id');
    	if(!count($statement_ids)) return;

        $file = Statement::batch_print($statement_ids);
        $this->layout = V('admin:transfer/statement/waiting_pdf', array('file'=>$file));
    }

    function download() {
       $form = Input::form();

       $path = Config::get('system.tmp_dir').'pdf/';
       $file = $path.$form['file'];
       if(file_exists($file)) Downloader::download($file, TRUE);
    }
}

class Transfer_Pdf_AJAX_Controller extends AJAX_Controller {
    function index_statement_pdf_keepalive() {
        $form = Input::form();
        $file = $form['file'];

        //创建keep_alive文件，让PHP脚本知道网页未关闭
        $path = Config::get('system.tmp_dir').'pdf/';

        $file = $path.$file;
        $keep_alive = $file.'.keep';

        //如果没有目标pdf文件则更新
        //keep_alive文件存在才进行更新
        if(!file_exists($file) && file_exists($keep_alive)){
            touch($keep_alive);
        }
        else{
            Output::$AJAX['success'] = 1;
        }
    }
}


