<?php

class Show_Controller extends _Controller
{
    public function billing($id = 0)
    {
        $statement = O('billing_statement', $id);
        if (!$statement->id) {
            URI::redirect('error/404');
        }
        echo V('show/billing', [
            'statement' => $statement,
            'vendor'=>$statement->vendor,
            ]);
    }
}
