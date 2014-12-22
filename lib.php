<?php

/* herein lies the social media donkey */

class sm  {

/* ------------- config ------------*/
    var $debug = TRUE;
    var $file = 'list.txt';
    var $csv_file = 'out.csv';
    var $process = array();
    var $urls = array();
    var $data = array();
    var $output = array();
    var $log = array();


    public  function sm($config) {
        //defaults
        $this->process['facebook']      = TRUE;
        $this->process['pinterest']     = TRUE;
        $this->process['twitter']       = TRUE;
        $this->process['google_plus']   = FALSE;

        foreach($config as $key => $value)
            $this->$key = $value;
    }

    /* ------------- processing functions ------------*/

    private function get_urls() {
        if(!is_readable($this->file))
            die(sprintf("%s: %s is not readible",__FUNCTION__, $this->file));

        $data = file($this->file);
        foreach($data as $url)
            if(!empty($url))
                $this->urls[] = trim($url);
    }


    public function process($output='echo') {
        $this->get_urls();
        $this->process_urls();
        if($output == 'csv') 
            $this->output_csv();
        else
            $this->output();
    }

    private function process_urls() {
        $this->_log(sprintf('%s: %s urls to process', __FUNCTION__, sizeof($this->urls)));

        //process the likes etc
        foreach($this->urls as $url)
            foreach($this->process as $function => $value)
                if($value == 1)
                    $this->data[$url][$function] = $this->$function($url);

        //make in to a nice array
        $headers = array_keys($this->process, 1);
        array_unshift($headers, 'url');
        $this->output[] = $headers;

        foreach($this->data as $url => $values)
            $this->output[] = array_merge(array('url' => $url),$values);
    }

    private function output()
    {
        var_dump($this->output);
    }

    private function output_csv() {

        if(!$fp = fopen($this->csv_file, 'w')) {
            $this->_log(sprintf("%s: %s is not writeable", __FUNCTION__, $this->csv_file));
            //out put instead
            $this->output();
            return;
        }

        foreach ($this->output as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
        $this->_log(sprintf("%s: out put csv to %s", __FUNCTION__, $this->csv_file));
    }


    /* ------------- sm functions ------------*/

    public  function facebook( $url ) {

        $api = file_get_contents( 'http://graph.facebook.com/?id=' . $url );

        $count = json_decode( $api );

        return $count->shares;
    }

    function google_plus ( $url ) {
        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_URL, "https://clients6.google.com/rpc" );
        curl_setopt( $curl, CURLOPT_POST, 1 );
        curl_setopt( $curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"' . $url . '","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]' );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Content-type: application/json' ) );
        $curl_results = curl_exec( $curl );
        curl_close( $curl );
        $json = json_decode( $curl_results, true );

        return intval( $json[0]['result']['metadata']['globalCounts']['count'] );
    }

    public function pinterest( $url ) {

        $api = file_get_contents( 'http://api.pinterest.com/v1/urls/count.json?callback%20&url=' . $url );

        $body = preg_replace( '/^receiveCount\((.*)\)$/', '\\1', $api );

        $count = json_decode( $body );

        return $count->count;

    }

    public function twitter( $url ) {

        $api = file_get_contents( 'https://cdn.api.twitter.com/1/urls/count.json?url=' . $url );

        $count = json_decode( $api );

        return $count->count;
    }

    /* ------------- aux functions ------------*/
    private function _log($message)
    {
        if($this->debug == TRUE)
            echo "$message\n";

        $this->log[] = $message;
    }
}
?>
